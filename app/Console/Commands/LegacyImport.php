<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Import des données legacy MyTicketO (base leweb_* via connexion `legacy`)
 * vers le schéma actuel. Idempotent (upsert par legacy_id), périmètre ACTIF
 * par défaut (événements à venir). Les codes de billets sont préservés à
 * l'identique pour que les billets déjà vendus restent scannables.
 *
 * Prérequis : charger le dump dans une base MySQL et renseigner LEGACY_DB_*.
 */
class LegacyImport extends Command
{
    protected $signature = 'legacy:import
        {--all : Importer aussi les événements passés (par défaut: à venir seulement)}
        {--fresh : Purger les données existantes (events, ventes, organisateurs, clients) avant import — conserve admins et données de référence}
        {--physical-events= : IDs d\'événements legacy (séparés par des virgules) dont les billets sont physiques imprimés — ex : 156}
        {--dry-run : Simuler sans écrire}';

    protected $description = 'Importer les données legacy MyTicketO vers le schéma actuel';

    private bool $dry = false;
    private array $map = [
        'owner_org' => [], 'owner_user' => [], 'user' => [],
        'event' => [], 'date' => [], 'cat' => [],
    ];

    /** Suivi de progression (publié dans le cache fichier, lu par l'UI admin). */
    private ?Carbon $startedAt = null;
    private array $counts = [
        'clients' => 0, 'organisateurs' => 0, 'events' => 0, 'schedules' => 0,
        'ticket_types' => 0, 'orders' => 0, 'tickets' => 0, 'checkins' => 0,
    ];
    private array $totals = [];

    private const STEP_TOTAL = 9;

    /**
     * Publier l'état de progression (store fichier : reste lisible pendant la
     * transaction DB de l'import). Ignoré en dry-run.
     */
    private function report(string $label, int $stepIndex, ?int $done = null, ?int $total = null): void
    {
        if ($this->dry) {
            return;
        }
        \App\Jobs\RunLegacyImport::setStatus([
            'state' => 'running',
            'step' => $label,
            'step_index' => $stepIndex,
            'step_total' => self::STEP_TOTAL,
            'item_done' => $done,
            'item_total' => $total,
            'counts' => $this->counts,
            'started_at' => optional($this->startedAt)->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');

        // Sanity check connexion legacy
        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Connexion `legacy` impossible : ' . $e->getMessage());
            $this->line('Charger le dump et renseigner LEGACY_DB_* dans .env.');
            return self::FAILURE;
        }

        $this->info(($this->dry ? '[DRY-RUN] ' : '') . 'Import legacy MyTicketO…');

        $eventIds = $this->activeLegacyEventIds();
        $this->line('Événements à importer : ' . count($eventIds));
        if (empty($eventIds)) {
            $this->warn('Aucun événement dans le périmètre.');
            \App\Jobs\RunLegacyImport::setStatus([
                'state' => 'done', 'step' => 'Aucun événement dans le périmètre.',
                'counts' => $this->counts, 'finished_at' => now()->toDateTimeString(),
            ]);
            return self::SUCCESS;
        }

        // Totaux (dénominateurs des barres de progression) — requêtes COUNT rapides.
        $this->startedAt = now();
        $lc = fn () => DB::connection('legacy');
        $this->totals = [
            'clients' => (int) $lc()->table('leweb_users')->count(),
            'organisateurs' => (int) $lc()->table('leweb_event')->whereIn('id', $eventIds)->distinct()->count('owner'),
            'events' => count($eventIds),
            'orders' => (int) $lc()->table('leweb_pay')->whereIn('id_event', $eventIds)->where('statut', 'PAID')->count(),
            'tickets' => (int) $lc()->table('leweb_ticket')->whereIn('id_event', $eventIds)->count(),
        ];
        $this->report('Préparation…', 0);

        try {
            DB::transaction(function () use ($eventIds) {
                if ($this->option('fresh') && !$this->dry) {
                    $this->report('Purge des données existantes…', 1);
                    $this->purgeExisting();
                }
                $this->importUsers();          // clients
                $this->importOwners($eventIds); // organisateurs (+ users)
                $this->importEvents($eventIds); // events (+ venues, catégories)
                $this->importSchedules($eventIds);
                $this->importTicketTypes($eventIds);
                $this->importOrdersAndPayments($eventIds);
                $this->importTickets($eventIds);
                $this->importCheckins($eventIds);

                if ($this->dry) {
                    // Rollback volontaire : on annule tout ce qui a été écrit
                    // pendant la simulation.
                    throw new DryRunRollback();
                }
            });
        } catch (DryRunRollback $e) {
            $this->info('[DRY-RUN] Simulation terminée (aucune donnée écrite).');
            return self::SUCCESS;
        }

        \App\Jobs\RunLegacyImport::setStatus([
            'state' => 'done',
            'step' => 'Import terminé.',
            'counts' => $this->counts,
            'started_at' => optional($this->startedAt)->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
        ]);
        $this->info('Import terminé.');
        return self::SUCCESS;
    }

    /** IDs des événements legacy dans le périmètre (à venir sauf --all). */
    private function activeLegacyEventIds(): array
    {
        $events = DB::connection('legacy')->table('leweb_event')->where('sup', 0)->get();
        if ($this->option('all')) {
            return $events->pluck('id')->all();
        }

        $dates = DB::connection('legacy')->table('leweb_date')->where('sup', 0)->get()
            ->groupBy('id_event');

        $ids = [];
        foreach ($events as $ev) {
            $eventDates = $dates->get($ev->id, collect());
            foreach ($eventDates as $d) {
                $ts = $this->parseLegacyDate($d->date_format);
                if ($ts && $ts->isFuture()) {
                    $ids[] = $ev->id;
                    break;
                }
            }
        }
        return $ids;
    }

    private function importUsers(): void
    {
        $rows = DB::connection('legacy')->table('leweb_users')->get();
        $clientType = DB::table('user_types')->where('name', 'client')->value('id') ?? 2;

        $total = count($rows);
        $this->report('Clients', 2, 0, $total);
        $i = 0;
        foreach ($rows as $u) {
            $user = User::firstOrNew(['legacy_id' => $u->id]);
            $user->name = $this->clean($u->nom) ?: ('Client ' . $u->id);
            $user->phone = $this->clean($u->tel);
            $user->email = $user->email ?: $this->syntheticEmail($u->tel, 'client', $u->id);
            $user->user_type_id = $clientType;
            $user->is_organizer = false;
            $user->status = ($u->statut ?? 1) ? 'active' : 'inactive';
            $user->legacy_md5 = $u->mdp;
            if (!$user->exists) {
                $user->password = Hash::make(Str::random(24));
            }
            $user->legacy_id = $u->id;
            if (true) {
                $user->save();
                $this->map['user'][$u->id] = $user->id;
            }
            if (++$i % 100 === 0) {
                $this->counts['clients'] = $i;
                $this->report('Clients', 2, $i, $total);
            }
        }
        $this->counts['clients'] = $total;
        $this->report('Clients', 2, $total, $total);
        $this->line('  users clients: ' . count($rows));
    }

    private function importOwners(array $eventIds): void
    {
        // N'importer que les owners ayant un événement dans le périmètre.
        $ownerIds = DB::connection('legacy')->table('leweb_event')
            ->whereIn('id', $eventIds)->pluck('owner')->unique()->filter()->all();

        $owners = DB::connection('legacy')->table('leweb_owner')->whereIn('id', $ownerIds)->get();
        $orgType = DB::table('user_types')->where('name', 'organizer')->value('id') ?? 3;

        $total = count($owners);
        $this->report('Organisateurs', 3, 0, $total);
        $i = 0;
        foreach ($owners as $o) {
            // 1) compte user de login pour l'organisateur.
            // Idempotence par email synthétique (unique) : legacy_id est un
            // entier réservé au mapping des clients, on ne le réutilise pas ici.
            $orgEmail = $this->syntheticEmail($o->tel, 'org', $o->id);
            $user = User::firstOrNew(['email' => $orgEmail]);
            $user->name = $this->clean($o->nom) ?: ('Organisateur ' . $o->id);
            $user->phone = $this->clean($o->tel);
            $user->user_type_id = $orgType;
            $user->is_organizer = true;
            $user->status = ($o->statut ?? 0) ? 'active' : 'inactive';
            $user->legacy_md5 = $o->mdp;
            if (!$user->exists) {
                $user->password = Hash::make(Str::random(24));
            }

            // 2) profil organizer
            $org = Organizer::firstOrNew(['legacy_id' => $o->id]);
            $org->name = $this->clean($o->nom) ?: ('Organisateur ' . $o->id);
            $org->contact_phone = $this->clean($o->tel);
            $org->status = ($o->statut ?? 0) ? 'active' : 'inactive';
            $org->is_active = (bool) ($o->statut ?? 0);
            $org->default_commission_percentage = 10.00; // legacy = 10% flat
            $org->legacy_id = $o->id;

            if (true) {
                $user->save();
                if (empty($org->slug)) {
                    $org->slug = $this->uniqueSlug(Organizer::class, $org->name);
                }
                $org->save();
                // lien pivot organizer_user
                if (!$org->users()->where('users.id', $user->id)->exists()) {
                    $org->users()->attach($user->id, ['role' => 'owner']);
                }
                $this->map['owner_org'][$o->id] = $org->id;
                $this->map['owner_user'][$o->id] = $user->id;
            }
            if (++$i % 20 === 0) {
                $this->counts['organisateurs'] = $i;
                $this->report('Organisateurs', 3, $i, $total);
            }
        }
        $this->counts['organisateurs'] = $total;
        $this->report('Organisateurs', 3, $total, $total);
        $this->line('  organisateurs: ' . count($owners));
    }

    private function importEvents(array $eventIds): void
    {
        $events = DB::connection('legacy')->table('leweb_event')->whereIn('id', $eventIds)->get();

        $total = count($events);
        $this->report('Événements', 4, 0, $total);
        $i = 0;
        foreach ($events as $ev) {
            $organizerId = $this->map['owner_org'][$ev->owner] ?? null;
            if (!$organizerId) {
                $this->warn("  event {$ev->id} sans organizer mappé — ignoré");
                continue;
            }

            $venueId = $this->resolveVenue($this->clean($ev->lieu), $organizerId);
            $categoryId = $this->resolveCategory($this->clean($ev->cat));

            $event = Event::firstOrNew(['legacy_id' => $ev->id]);
            $event->organizer_id = $organizerId;
            $event->category_id = $categoryId;
            $event->venue_id = $venueId;
            $event->title = $this->clean($ev->titre) ?: ('Événement ' . $ev->id);
            $event->description = $this->clean($ev->titre) ?: '';
            $event->image_file = $this->clean($ev->image) ?: null; // à recopier ensuite
            $published = (int) ($ev->statut ?? 0) === 1;
            $event->status = $published ? 'published' : 'draft';
            $event->approval_status = $published ? 'approved' : 'pending';
            $event->approved_at = $published ? now() : null;
            $event->published_at = $published ? now() : null;
            $event->payout_mode = 'deferred';
            $event->payout_settled_at = now(); // historique déjà réglé côté legacy
            $event->legacy_id = $ev->id;

            if (true) {
                if (empty($event->slug)) {
                    $event->slug = $this->uniqueSlug(Event::class, $event->title);
                }
                $event->save();
                $this->map['event'][$ev->id] = $event->id;
            }
            $this->counts['events'] = ++$i;
            if ($i % 10 === 0) {
                $this->report('Événements', 4, $i, $total);
            }
        }
        $this->counts['events'] = $total;
        $this->report('Événements', 4, $total, $total);
        $this->line('  events: ' . count($events));
    }

    private function importSchedules(array $eventIds): void
    {
        $dates = DB::connection('legacy')->table('leweb_date')
            ->whereIn('id_event', $eventIds)->where('sup', 0)->get();

        $this->report('Dates', 5, 0, count($dates));
        $n = 0;
        foreach ($dates as $d) {
            $eventId = $this->map['event'][$d->id_event] ?? null;
            if (!$eventId) continue;
            $starts = $this->parseLegacyDate($d->date_format);
            if (!$starts) continue;

            $sch = EventSchedule::firstOrNew(['legacy_id' => $d->id]);
            $sch->event_id = $eventId;
            $sch->starts_at = $starts;
            $sch->ends_at = (clone $starts)->addHours(3); // legacy n'a pas de fin
            $sch->status = 'active';
            $sch->legacy_id = $d->id;
            if (true) { $sch->save(); $this->map['date'][$d->id] = $sch->id; }
            $n++;
        }
        $this->counts['schedules'] = $n;
        $this->report('Dates', 5, $n, count($dates));
        $this->line('  schedules: ' . $n);
    }

    private function importTicketTypes(array $eventIds): void
    {
        $cats = DB::connection('legacy')->table('leweb_cat')
            ->whereIn('id_event', $eventIds)->where('sup', 0)->get();

        $this->report('Types de billets', 6, 0, count($cats));
        $n = 0;
        foreach ($cats as $c) {
            $eventId = $this->map['event'][$c->id_event] ?? null;
            if (!$eventId) continue;

            $tt = TicketType::firstOrNew(['legacy_id' => $c->id]);
            $tt->event_id = $eventId;
            $tt->name = $this->clean($c->titre) ?: 'Standard';
            $tt->price = (int) $c->prix;
            $tt->currency = 'XAF';
            $tt->available_quantity = is_numeric($c->place) ? (int) $c->place : null;
            $tt->status = 'active';
            $tt->legacy_id = $c->id;
            if (true) { $tt->save(); $this->map['cat'][$c->id] = $tt->id; }
            $n++;
        }
        $this->counts['ticket_types'] = $n;
        $this->report('Types de billets', 6, $n, count($cats));
        $this->line('  ticket_types: ' . $n);
    }

    private function importOrdersAndPayments(array $eventIds): void
    {
        $pays = DB::connection('legacy')->table('leweb_pay')
            ->whereIn('id_event', $eventIds)
            ->where('statut', 'PAID')
            ->get();

        $total = count($pays);
        $this->report('Commandes + paiements', 7, 0, $total);
        $n = 0;
        foreach ($pays as $p) {
            $eventId = $this->map['event'][$p->id_event] ?? null;
            if (!$eventId) continue;
            $organizerId = Event::where('id', $eventId)->value('organizer_id');
            $buyerId = $this->map['user'][$p->id_user] ?? null;

            $montant = (int) $p->montant;
            $commission = round($montant * 0.10, 2);
            $net = round($montant - $commission, 2);

            $order = Order::firstOrNew(['legacy_id' => $p->id]);
            $order->organizer_id = $organizerId;
            $order->buyer_id = $buyerId;
            $order->currency = 'XAF';
            $order->subtotal_amount = $net;
            $order->fees_amount = $commission;
            $order->commission_percentage = 10;
            $order->tax_amount = 0;
            $order->total_amount = $montant;
            $order->status = 'paid';
            $order->reference = $this->clean($p->ref) ?: ('ORD-LEG-' . $p->id);
            $order->placed_at = $this->parseTs($p->created_at ?? null) ?? now();
            $order->is_guest_order = $buyerId ? false : true;
            $order->legacy_id = $p->id;

            if (true) {
                $order->save();

                // Paiement associé
                $pay = Payment::firstOrNew(['order_id' => $order->id]);
                $pay->order_id = $order->id;
                $pay->amount = $montant;
                $pay->provider = $this->clean($p->system) ?: 'airtelmoney';
                $pay->status = 'success';
                $pay->provider_txn_ref = $this->clean($p->trans_id) ?: null;
                $pay->paid_at = $order->placed_at;
                $pay->save();
            }
            $n++;
            if ($n % 200 === 0) {
                $this->counts['orders'] = $n;
                $this->report('Commandes + paiements', 7, $n, $total);
            }
        }
        $this->counts['orders'] = $n;
        $this->report('Commandes + paiements', 7, $n, $total);
        $this->line('  orders + payments: ' . $n);
    }

    private function importTickets(array $eventIds): void
    {
        // Insertion GROUPÉE par chunks (23k+ billets) pour rester rapide même
        // via une requête web. Idempotence assurée par la purge (--fresh) du
        // flux de remplacement.
        $now = now();
        $n = 0;
        $total = $this->totals['tickets'] ?? 0;
        // Événements legacy dont les billets sont des QR physiques imprimés
        // (ex : stock pré-généré de l'event 156) — taggés 'physical' au lieu de
        // 'online' pour un reporting correct (n'affecte pas la scannabilité).
        $physicalEvents = collect(explode(',', (string) $this->option('physical-events')))
            ->map(fn ($id) => (int) trim($id))->filter()->all();
        $this->report('Billets', 8, 0, $total);
        DB::connection('legacy')->table('leweb_ticket')
            ->whereIn('id_event', $eventIds)
            ->orderBy('id')
            ->chunk(1000, function ($chunk) use (&$n, $now, $total, $physicalEvents) {
                $rows = [];
                foreach ($chunk as $t) {
                    $eventId = $this->map['event'][$t->id_event] ?? null;
                    if (!$eventId) {
                        continue;
                    }
                    $rows[] = [
                        'legacy_id' => $t->id,
                        'event_id' => $eventId,
                        'ticket_type_id' => $this->map['cat'][$t->id_cat] ?? null,
                        // CODE PRÉSERVÉ (le QR imprimé/envoyé) — non négociable.
                        'code' => $this->clean($t->ref),
                        'status' => ((int) ($t->statut ?? 0) === 1) ? 'used' : 'issued',
                        'ticket_source' => in_array((int) $t->id_event, $physicalEvents, true) ? 'physical' : 'online',
                        'issued_at' => $this->parseTs($t->date_crea ?? null) ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) {
                    Ticket::insert($rows);
                    $n += count($rows);
                }
                $this->counts['tickets'] = $n;
                $this->report('Billets', 8, $n, $total);
            });
        $this->counts['tickets'] = $n;
        $this->report('Billets', 8, $n, $total);
        $this->line('  tickets (codes préservés): ' . $n);
    }

    private function importCheckins(array $eventIds): void
    {
        // Map code -> ticket_id (une seule requête) pour éviter un lookup par scan.
        $codeToId = Ticket::whereIn('event_id', array_values($this->map['event']))
            ->pluck('id', 'code');

        $now = now();
        $n = 0;
        $this->report('Scans', 9, 0, null);
        DB::connection('legacy')->table('leweb_scan')
            ->whereIn('id_event', $eventIds)
            ->orderBy('id')
            ->chunk(2000, function ($chunk) use (&$n, $codeToId, $now) {
                $rows = [];
                foreach ($chunk as $s) {
                    $ticketId = $codeToId[$this->clean($s->ref)] ?? null;
                    if (!$ticketId) {
                        continue;
                    }
                    $rows[] = [
                        'ticket_id' => $ticketId,
                        'result' => ((int) ($s->statut ?? 0) === 1) ? 'valid' : 'invalid',
                        'scanned_at' => $this->parseTs($s->date_crea ?? null) ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) {
                    DB::table('checkins')->insert($rows);
                    $n += count($rows);
                }
                $this->counts['checkins'] = $n;
                $this->report('Scans', 9, $n, null);
            });
        $this->counts['checkins'] = $n;
        $this->report('Scans', 9, $n, null);
        $this->line('  checkins: ' . $n);
    }

    /**
     * Purger les données transactionnelles/métier existantes avant un import
     * "remplacement". CONSERVE : admins, user_types, event_categories, roles,
     * privileges, settings, banners, hero_banners.
     */
    private function purgeExisting(): void
    {
        $this->warn('  ⚠ Purge des données existantes…');

        $adminTypeId = DB::table('user_types')->where('name', 'admin')->value('id');

        // On utilise DELETE (et non TRUNCATE) : TRUNCATE provoque un COMMIT
        // implicite qui casserait la transaction de l'import.
        $tables = [
            'notifications', 'checkins', 'tickets', 'payments', 'order_items', 'orders',
            'ticket_prices', 'ticket_types', 'event_schedules', 'event_recurrence_rules',
            'events', 'organizer_balances', 'payouts', 'organizer_user', 'venues', 'organizers',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
            // Utilisateurs : supprimer clients + organisateurs, garder les admins.
            $usersQuery = DB::table('users');
            if ($adminTypeId) {
                $usersQuery->where('user_type_id', '!=', $adminTypeId)->orWhereNull('user_type_id');
            }
            $usersQuery->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->line('  ✔ Purge terminée (admins et données de référence conservés)');
    }

    // ---------- Helpers ----------

    private function resolveVenue(?string $name, ?int $organizerId): ?int
    {
        if (!$name) return null;

        // Le legacy n'a qu'un nom de lieu (pas de ville/adresse séparées).
        $venue = Venue::firstOrCreate(
            ['name' => $name],
            [
                'organizer_id' => $organizerId,
                'city' => 'Libreville', // défaut Gabon, éditable ensuite
                'address' => $name,
                'country' => 'Gabon',
                'status' => 'active',
            ]
        );
        return $venue->id;
    }

    private function resolveCategory(?string $legacyCat): ?int
    {
        $map = ['concert' => 'Concert', 'cinema' => 'Cinéma', 'sport' => 'Sport', 'theatre' => 'Théâtre'];
        $target = $map[strtolower(trim((string) $legacyCat))] ?? 'Autres';
        $id = DB::table('event_categories')->where('name', $target)->value('id');
        return $id ?: DB::table('event_categories')->where('name', 'Autres')->value('id');
    }

    private function parseLegacyDate(?string $s): ?Carbon
    {
        $s = trim((string) $s);
        if ($s === '') return null;
        try { return Carbon::createFromFormat('Y-m-d H:i', $s); }
        catch (\Throwable $e) {
            try { return Carbon::parse($s); } catch (\Throwable $e2) { return null; }
        }
    }

    private function parseTs($s): ?Carbon
    {
        if (!$s) return null;
        try { return Carbon::parse($s); } catch (\Throwable $e) { return null; }
    }

    private function syntheticEmail(?string $tel, string $prefix, $id): string
    {
        // Basé sur l'id legacy (unique par table) pour garantir l'unicité de
        // l'email même quand plusieurs comptes partagent le même téléphone.
        return "{$prefix}-{$id}@legacy.myticket-o.net";
    }

    private function clean($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function uniqueSlug(string $modelClass, string $base): string
    {
        $slug = Str::slug($base) ?: Str::random(8);
        $original = $slug;
        $i = 1;
        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}

/**
 * Exception marqueur pour annuler la transaction en mode dry-run.
 */
class DryRunRollback extends \RuntimeException
{
}
