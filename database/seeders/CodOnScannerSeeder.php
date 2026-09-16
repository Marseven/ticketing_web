<?php

namespace Database\Seeders;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Compte de scan (app mobile) rattaché à l'organisateur Cod'On.
 *
 * Exécution :
 *   php artisan db:seed --class=CodOnScannerSeeder
 *
 * L'app mobile exige un utilisateur is_organizer=true, lié à l'organisateur
 * qui possède l'événement (ScanController), pour scanner ses billets.
 * Idempotent (updateOrCreate sur l'email).
 */
class CodOnScannerSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = Organizer::where('slug', 'cod-on')->first();
        if (!$organizer) {
            $this->command->error("Organisateur 'cod-on' introuvable — lance d'abord MadeInGabaoEventSeeder.");
            return;
        }

        $email = 'scan@codon.ga';
        $password = 'Codon@Scan2026';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => "Scanner Cod'On",
                'password' => Hash::make($password),
                'is_organizer' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Rattacher au compte organisateur Cod'On (autorise le scan de ses events)
        $user->organizers()->syncWithoutDetaching([
            $organizer->id => ['role' => 'scanner', 'permissions' => json_encode(['scan_tickets'])],
        ]);

        $this->command->info('✅ Compte scanner Cod\'On prêt :');
        $this->command->info("   Email    : {$email}");
        $this->command->info("   Password : {$password}");
        $this->command->info("   Organisateur : {$organizer->name} (#{$organizer->id})");
    }
}
