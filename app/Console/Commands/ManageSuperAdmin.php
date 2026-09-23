<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Accorde ou retire le rôle de super administrateur.
 *
 * Le rôle ouvre la supervision de la plateforme : état technique,
 * fréquentation, flux financiers en cours. Il se donne à la main, un compte à
 * la fois, et la commande dit toujours qui le détient après coup — on ne
 * découvre pas par hasard qui peut voir ces écrans.
 */
class ManageSuperAdmin extends Command
{
    protected $signature = 'admin:super
        {email? : Adresse du compte concerné}
        {--revoke : Retirer le rôle au lieu de l\'accorder}
        {--list : Se contenter de lister les super administrateurs}';

    protected $description = 'Accorde, retire ou liste le rôle de super administrateur';

    public function handle(): int
    {
        $role = Role::where('slug', Role::SUPER_ADMIN)->first();

        if (! $role) {
            $this->error('Le rôle super administrateur n\'existe pas. Lancer « php artisan migrate ».');

            return self::FAILURE;
        }

        $email = $this->argument('email');

        if ($this->option('list') || ! $email) {
            $this->listHolders($role);

            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte avec l'adresse {$email}.");

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $user->roles()->detach($role->id);
            $this->info("Rôle retiré à {$user->email}.");
        } else {
            $user->roles()->syncWithoutDetaching([$role->id]);
            $this->info("Rôle accordé à {$user->email}.");
        }

        $this->newLine();
        $this->listHolders($role);

        return self::SUCCESS;
    }

    private function listHolders(Role $role): void
    {
        $holders = User::whereHas('roles', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))
            ->orderBy('email')
            ->get(['name', 'email']);

        if ($holders->isEmpty()) {
            $this->warn('Aucun super administrateur. Personne ne peut ouvrir la supervision.');
            $this->suggestCandidates();

            return;
        }

        $this->line('Super administrateurs :');

        foreach ($holders as $holder) {
            $this->line('  · ' . $holder->email . ' (' . $holder->name . ')');
        }
    }

    /**
     * Propose les comptes à qui accorder le rôle.
     *
     * Sans cette liste, « personne ne détient le rôle » laisse chercher :
     * l'exploitant sait qu'il lui manque quelque chose, pas à quelle adresse
     * l'accorder.
     */
    private function suggestCandidates(): void
    {
        $candidates = User::whereHas('roles', fn ($q) => $q->where('slug', Role::ADMIN))
            ->orderBy('email')
            ->limit(10)
            ->get(['name', 'email']);

        if ($candidates->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->line('Comptes administrateurs, à qui le rôle peut être accordé :');

        foreach ($candidates as $candidate) {
            $this->line('  php artisan admin:super ' . $candidate->email);
        }
    }
}
