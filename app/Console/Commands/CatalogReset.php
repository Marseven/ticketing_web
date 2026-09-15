<?php

namespace App\Console\Commands;

use Database\Seeders\CategorySeeder;
use Database\Seeders\VenueSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remise à zéro du catalogue et des ventes : vide les événements, catégories,
 * lieux et paiements (+ toutes leurs dépendances), puis reseed optionnellement
 * les données de base.
 *
 * CONSERVE : utilisateurs, admins, organisateurs, rôles/privilèges, user_types,
 * réglages (settings/branding), bannières. Supprime aussi les tables legacy
 * leweb_* (import MyTicketO abandonné).
 */
class CatalogReset extends Command
{
    protected $signature = 'catalog:reset
        {--seed : Reseeder les catégories de base après nettoyage}
        {--venues : Reseeder aussi les lieux de base (Gabon)}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Vider événements, catégories, lieux et paiements (+ dépendances) et reseeder les données de base';

    /** Tables vidées, enfants d'abord (FK satisfaites même sans désactivation). */
    private array $tables = [
        'checkins',
        'tickets',
        'ticket_inventories',
        'order_items',
        'payment_transactions',
        'payments',
        'orders',
        'ticket_prices',
        'ticket_types',
        'event_schedules',
        'event_recurrence_rules',
        'payouts',
        'organizer_balances',
        'reports',
        'notifications',
        'events',
        'venues',
        'event_categories',
    ];

    private array $legacyTables = [
        'leweb_admin', 'leweb_cat', 'leweb_date', 'leweb_event', 'leweb_owner',
        'leweb_pay', 'leweb_scan', 'leweb_ticket', 'leweb_users',
    ];

    public function handle(): int
    {
        if (!$this->option('force')
            && !$this->confirm('Vider événements, catégories, lieux et paiements (+ billets, commandes, scans) ? Action IRRÉVERSIBLE.')) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($this->tables as $t) {
                if (Schema::hasTable($t)) {
                    $n = DB::table($t)->count();
                    DB::table($t)->delete();
                    $this->line("  vidé : {$t} ({$n})");
                }
            }
            // Tables legacy MyTicketO (import abandonné)
            foreach ($this->legacyTables as $t) {
                DB::statement("DROP TABLE IF EXISTS `{$t}`");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('✔ Nettoyage terminé (utilisateurs, organisateurs, rôles, réglages et bannières conservés).');

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => CategorySeeder::class, '--force' => true]);
            $this->info('✔ Catégories de base reseedées.');
        }
        if ($this->option('venues')) {
            $this->call('db:seed', ['--class' => VenueSeeder::class, '--force' => true]);
            $this->info('✔ Lieux de base reseedés.');
        }

        return self::SUCCESS;
    }
}
