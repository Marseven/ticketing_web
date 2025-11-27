<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use App\Models\Organizer;
use Illuminate\Console\Command;

class FixOrganizerRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:organizer-roles {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing organizer roles for users who are organizers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Récupérer le rôle organisateur
        $organizerRole = Role::where('slug', Role::ORGANIZER)->first();

        if (!$organizerRole) {
            $this->error('❌ Organizer role not found in database!');
            return 1;
        }

        $this->info("✅ Found organizer role: {$organizerRole->name} (ID: {$organizerRole->id})");
        $this->newLine();

        // Trouver tous les utilisateurs qui ont is_organizer = true
        $usersWithFlag = User::where('is_organizer', true)->get();

        $this->info("📊 Found {$usersWithFlag->count()} users with is_organizer = true");

        $fixed = 0;
        $alreadyHasRole = 0;

        foreach ($usersWithFlag as $user) {
            $hasRole = $user->hasRole(Role::ORGANIZER);

            if (!$hasRole) {
                $this->warn("⚠️  User #{$user->id} ({$user->name}) has is_organizer=true but no organizer role");

                if (!$dryRun) {
                    $user->roles()->attach($organizerRole->id, [
                        'assigned_at' => now(),
                        'assigned_by' => null,
                    ]);
                    $this->info("   ✅ Fixed: Assigned organizer role");
                }

                $fixed++;
            } else {
                $this->line("✓ User #{$user->id} ({$user->name}) already has organizer role");
                $alreadyHasRole++;
            }
        }

        $this->newLine();

        // Trouver les utilisateurs dans la table organizer_user mais qui n'ont pas is_organizer = true
        $usersInOrganizerTable = \DB::table('organizer_user')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        $this->info("📊 Found {$usersInOrganizerTable->count()} users in organizer_user pivot table");

        $needsFlag = 0;

        foreach ($usersInOrganizerTable as $userId) {
            $user = User::find($userId);

            if (!$user) {
                $this->warn("⚠️  User #{$userId} in organizer_user table but not found in users table");
                continue;
            }

            if (!$user->is_organizer) {
                $this->warn("⚠️  User #{$user->id} ({$user->name}) is in organizer_user but is_organizer=false");

                if (!$dryRun) {
                    $user->is_organizer = true;
                    $user->save();
                    $this->info("   ✅ Fixed: Set is_organizer=true");

                    // Assigner le rôle aussi
                    if (!$user->hasRole(Role::ORGANIZER)) {
                        $user->roles()->attach($organizerRole->id, [
                            'assigned_at' => now(),
                            'assigned_by' => null,
                        ]);
                        $this->info("   ✅ Fixed: Assigned organizer role");
                    }
                }

                $needsFlag++;
            }
        }

        $this->newLine();
        $this->info('📈 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users with is_organizer=true', $usersWithFlag->count()],
                ['Already had role', $alreadyHasRole],
                ['Role assigned', $fixed],
                ['Needed is_organizer flag', $needsFlag],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ All fixes applied successfully!');
        }

        return 0;
    }
}
