<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class LegacyImportController extends Controller
{
    /** Tables métier dont on affiche le compteur. */
    private array $countedTables = [
        'events', 'event_schedules', 'ticket_types', 'orders', 'payments',
        'tickets', 'checkins', 'venues', 'organizers', 'users',
    ];

    /**
     * État : connexion legacy, compteurs actuels, compteurs déjà importés,
     * périmètre disponible côté legacy.
     */
    public function status(): JsonResponse
    {
        $current = [];
        foreach ($this->countedTables as $t) {
            $current[$t] = DB::table($t)->count();
        }

        $imported = [
            'events' => DB::table('events')->whereNotNull('legacy_id')->count(),
            'tickets' => DB::table('tickets')->whereNotNull('legacy_id')->count(),
            'orders' => DB::table('orders')->whereNotNull('legacy_id')->count(),
        ];

        // "Connecté" = les tables leweb_* sont présentes (dump chargé).
        $legacy = ['connected' => false, 'loaded' => false];
        try {
            DB::connection('legacy')->getPdo();
            if (\Illuminate\Support\Facades\Schema::connection('legacy')->hasTable('leweb_event')) {
                $legacy = [
                    'connected' => true,
                    'loaded' => true,
                    'events' => DB::connection('legacy')->table('leweb_event')->where('sup', 0)->count(),
                    'owners' => DB::connection('legacy')->table('leweb_owner')->count(),
                    'users' => DB::connection('legacy')->table('leweb_users')->count(),
                    'tickets' => DB::connection('legacy')->table('leweb_ticket')->count(),
                    'pay_paid' => DB::connection('legacy')->table('leweb_pay')->where('statut', 'PAID')->count(),
                ];
            } else {
                $legacy = ['connected' => true, 'loaded' => false];
            }
        } catch (\Throwable $e) {
            $legacy['error'] = $e->getMessage();
        }

        $import = \App\Jobs\RunLegacyImport::getStatus();

        return response()->json([
            'success' => true,
            'data' => ['current' => $current, 'imported' => $imported, 'legacy' => $legacy, 'import' => $import],
        ]);
    }

    /**
     * Charger le dump .sql MyTicketO (tables leweb_*) dans la base.
     * Les tables leweb_* ne collisionnent pas avec les tables de l'app.
     */
    public function uploadDump(Request $request): JsonResponse
    {
        $request->validate([
            'dump' => 'required|file|max:51200', // 50 Mo
        ]);

        $sql = file_get_contents($request->file('dump')->getRealPath());
        if ($sql === false || stripos($sql, 'leweb_') === false) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier ne ressemble pas à un dump MyTicketO (tables leweb_* introuvables).',
            ], 422);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            // Nettoyer d'éventuelles tables leweb_* déjà chargées, puis exécuter
            // le dump statement par statement (un envoi unique dépasserait
            // max_allowed_packet => "MySQL server has gone away").
            $this->dropLegacyTables();
            $this->executeSqlDump($sql);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec du chargement du dump : ' . $e->getMessage(),
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Dump chargé avec succès.']);
    }

    /**
     * Supprimer les tables legacy leweb_* (nettoyage post-import).
     */
    public function cleanup(): JsonResponse
    {
        try {
            $this->dropLegacyTables();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Tables legacy supprimées.']);
    }

    /**
     * Exécuter un dump SQL statement par statement (respecte les chaînes et
     * les commentaires). Évite le dépassement de max_allowed_packet d'un envoi
     * unique du fichier complet.
     */
    private function executeSqlDump(string $sql): void
    {
        $pdo = DB::connection('legacy')->getPdo();
        $len = strlen($sql);
        $buf = '';
        $inStr = false;
        $strCh = '';
        $i = 0;

        $exec = function (string $stmt) use ($pdo) {
            $stmt = trim($stmt);
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        };

        while ($i < $len) {
            $ch = $sql[$i];

            if ($inStr) {
                $buf .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {
                    $buf .= $sql[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($ch === $strCh) {
                    if ($i + 1 < $len && $sql[$i + 1] === $strCh) { // '' échappé
                        $buf .= $sql[$i + 1];
                        $i += 2;
                        continue;
                    }
                    $inStr = false;
                }
                $i++;
                continue;
            }

            // Hors chaîne
            if ($ch === "'" || $ch === '"') {
                $inStr = true;
                $strCh = $ch;
                $buf .= $ch;
                $i++;
                continue;
            }
            if (($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') || $ch === '#') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }
            if ($ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = ($end === false) ? $len : $end + 2;
                continue;
            }
            if ($ch === ';') {
                $exec($buf);
                $buf = '';
                $i++;
                continue;
            }
            $buf .= $ch;
            $i++;
        }
        $exec($buf);
    }

    private function dropLegacyTables(): void
    {
        $tables = ['leweb_admin', 'leweb_cat', 'leweb_date', 'leweb_event', 'leweb_owner',
            'leweb_pay', 'leweb_scan', 'leweb_ticket', 'leweb_users'];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $t) {
                DB::statement("DROP TABLE IF EXISTS `{$t}`");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Aperçu : COMPTE ce qui serait importé, sans rien écrire (instantané).
     * On ne simule plus l'insert/rollback (trop lent sur gros volumes).
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $eventIds = $this->activeLegacyEventIds($request->boolean('all'));

            if (empty($eventIds)) {
                return response()->json(['success' => true, 'data' => ['projected' => [], 'message' => 'Aucun événement dans le périmètre.']]);
            }

            $lc = fn () => DB::connection('legacy');
            $ownerIds = $lc()->table('leweb_event')->whereIn('id', $eventIds)->pluck('owner')->unique()->filter();

            $projected = [
                'events' => count($eventIds),
                'organizers' => $ownerIds->count(),
                'clients' => $lc()->table('leweb_users')->count(),
                'schedules' => $lc()->table('leweb_date')->whereIn('id_event', $eventIds)->where('sup', 0)->count(),
                'ticket_types' => $lc()->table('leweb_cat')->whereIn('id_event', $eventIds)->where('sup', 0)->count(),
                'orders' => $lc()->table('leweb_pay')->whereIn('id_event', $eventIds)->where('statut', 'PAID')->count(),
                'tickets' => $lc()->table('leweb_ticket')->whereIn('id_event', $eventIds)->count(),
            ];

            return response()->json(['success' => true, 'data' => ['projected' => $projected]]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * IDs des événements legacy dans le périmètre (mêmes règles que la commande).
     */
    private function activeLegacyEventIds(bool $all): array
    {
        $events = DB::connection('legacy')->table('leweb_event')->where('sup', 0)->get();
        if ($all) {
            return $events->pluck('id')->all();
        }

        $dates = DB::connection('legacy')->table('leweb_date')->where('sup', 0)->get()->groupBy('id_event');
        $ids = [];
        foreach ($events as $ev) {
            foreach (($dates->get($ev->id) ?? collect()) as $d) {
                if ($d->date_format && strtotime($d->date_format) > time()) {
                    $ids[] = $ev->id;
                    break;
                }
            }
        }
        return $ids;
    }

    /**
     * Exécution réelle. Requiert une confirmation explicite.
     */
    public function run(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'confirm' => 'required|string',
            'fresh' => 'boolean',
            'all' => 'boolean',
        ]);
        if ($validator->fails() || strtoupper(trim($request->input('confirm'))) !== 'REMPLACER') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmation requise : tapez REMPLACER pour lancer l\'import.',
            ], 422);
        }

        // Les tables legacy doivent être chargées.
        try {
            if (!\Illuminate\Support\Facades\Schema::connection('legacy')->hasTable('leweb_event')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée legacy chargée. Importez d\'abord le dump .sql.',
                ], 400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connexion à la base impossible : ' . $e->getMessage(),
            ], 400);
        }

        // Ne pas relancer si un import est déjà en cours.
        $existing = \App\Jobs\RunLegacyImport::getStatus();
        if (($existing['state'] ?? null) === 'running' || ($existing['state'] ?? null) === 'queued') {
            return response()->json(['success' => false, 'message' => 'Un import est déjà en cours.'], 409);
        }

        // Lancer en ARRIÈRE-PLAN (queue database) : la requête web répond tout
        // de suite, l'import tourne dans le worker (traité par le cron), sans
        // timeout. La page suit la progression via /status.
        \App\Jobs\RunLegacyImport::setStatus([
            'state' => 'queued',
            'queued_at' => now()->toDateTimeString(),
        ]);

        \App\Jobs\RunLegacyImport::dispatch($request->boolean('fresh'), $request->boolean('all'))
            ->onConnection('database');

        return response()->json([
            'success' => true,
            'message' => 'Import lancé en arrière-plan.',
            'data' => ['state' => 'queued'],
        ]);
    }
}
