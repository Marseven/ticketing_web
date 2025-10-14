<?php

namespace Database\Seeders;

use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les privilèges d'abord
        $this->createPrivileges();

        // Créer les rôles système
        $this->createSystemRoles();

        // Assigner les privilèges aux rôles
        $this->assignPrivilegesToRoles();
    }

    /**
     * Créer les privilèges de base
     */
    private function createPrivileges(): void
    {
        $adminTypeId = \App\Models\UserType::where('name', 'admin')->first()->id;
        $clientTypeId = \App\Models\UserType::where('name', 'client')->first()->id;

        // Privilèges pour les administrateurs
        $adminPrivileges = [
            // Administration
            ['name' => '[Admin] Accès Administration', 'slug' => 'admin.access', 'module' => 'admin', 'action' => 'access', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Voir Rapports', 'slug' => 'admin.reports', 'module' => 'admin', 'action' => 'view', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Paramètres Système', 'slug' => 'admin.settings', 'module' => 'admin', 'action' => 'manage', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Voir Logs', 'slug' => 'admin.logs', 'module' => 'admin', 'action' => 'view', 'user_type_id' => $adminTypeId],

            // Gestion complète des utilisateurs
            ['name' => '[Admin] Voir Utilisateurs', 'slug' => 'admin.users.view', 'module' => 'users', 'action' => 'view', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Créer Utilisateurs', 'slug' => 'admin.users.create', 'module' => 'users', 'action' => 'create', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Modifier Utilisateurs', 'slug' => 'admin.users.update', 'module' => 'users', 'action' => 'update', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Supprimer Utilisateurs', 'slug' => 'admin.users.delete', 'module' => 'users', 'action' => 'delete', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Gérer Utilisateurs', 'slug' => 'admin.users.manage', 'module' => 'users', 'action' => 'manage', 'user_type_id' => $adminTypeId],

            // Gestion des organisateurs (admin)
            ['name' => '[Admin] Voir Organisateurs', 'slug' => 'admin.organizers.view', 'module' => 'organizers', 'action' => 'view', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Créer Organisateurs', 'slug' => 'admin.organizers.create', 'module' => 'organizers', 'action' => 'create', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Modifier Organisateurs', 'slug' => 'admin.organizers.update', 'module' => 'organizers', 'action' => 'update', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Supprimer Organisateurs', 'slug' => 'admin.organizers.delete', 'module' => 'organizers', 'action' => 'delete', 'user_type_id' => $adminTypeId],

            // Gestion des événements (admin)
            ['name' => '[Admin] Voir tous les Événements', 'slug' => 'admin.events.view', 'module' => 'events', 'action' => 'view', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Gérer tous les Événements', 'slug' => 'admin.events.manage', 'module' => 'events', 'action' => 'manage', 'user_type_id' => $adminTypeId],

            // Gestion des paiements (admin)
            ['name' => '[Admin] Voir tous les Paiements', 'slug' => 'admin.payments.view', 'module' => 'payments', 'action' => 'view', 'user_type_id' => $adminTypeId],
            ['name' => '[Admin] Gérer Paiements', 'slug' => 'admin.payments.manage', 'module' => 'payments', 'action' => 'manage', 'user_type_id' => $adminTypeId],
        ];

        // Privilèges pour les clients (organisateurs et clients simples)
        $clientPrivileges = [
            // Authentification
            ['name' => '[Client] Accès Authentification', 'slug' => 'auth.access', 'module' => 'auth', 'action' => 'access', 'user_type_id' => $clientTypeId],

            // Événements (pour organisateurs)
            ['name' => '[Client] Voir Événements', 'slug' => 'events.view', 'module' => 'events', 'action' => 'view', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Créer Événements', 'slug' => 'events.create', 'module' => 'events', 'action' => 'create', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Modifier Événements', 'slug' => 'events.update', 'module' => 'events', 'action' => 'update', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Supprimer Événements', 'slug' => 'events.delete', 'module' => 'events', 'action' => 'delete', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Gérer Événements', 'slug' => 'events.manage', 'module' => 'events', 'action' => 'manage', 'user_type_id' => $clientTypeId],

            // Commandes
            ['name' => '[Client] Voir Commandes', 'slug' => 'orders.view', 'module' => 'orders', 'action' => 'view', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Créer Commandes', 'slug' => 'orders.create', 'module' => 'orders', 'action' => 'create', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Modifier Commandes', 'slug' => 'orders.update', 'module' => 'orders', 'action' => 'update', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Gérer Commandes', 'slug' => 'orders.manage', 'module' => 'orders', 'action' => 'manage', 'user_type_id' => $clientTypeId],

            // Paiements
            ['name' => '[Client] Voir Paiements', 'slug' => 'payments.view', 'module' => 'payments', 'action' => 'view', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Traiter Paiements', 'slug' => 'payments.process', 'module' => 'payments', 'action' => 'create', 'user_type_id' => $clientTypeId],

            // Billets
            ['name' => '[Client] Voir Billets', 'slug' => 'tickets.view', 'module' => 'tickets', 'action' => 'view', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Créer Billets', 'slug' => 'tickets.create', 'module' => 'tickets', 'action' => 'create', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Valider Billets', 'slug' => 'tickets.validate', 'module' => 'tickets', 'action' => 'update', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Gérer Billets', 'slug' => 'tickets.manage', 'module' => 'tickets', 'action' => 'manage', 'user_type_id' => $clientTypeId],

            // Scanning (pour organisateurs)
            ['name' => '[Client] Accès Scanning', 'slug' => 'scanning.access', 'module' => 'scanning', 'action' => 'access', 'user_type_id' => $clientTypeId],
            ['name' => '[Client] Scanner Billets', 'slug' => 'scanning.scan', 'module' => 'scanning', 'action' => 'create', 'user_type_id' => $clientTypeId],

            // Accès organisateur
            ['name' => '[Client] Accès Organisateurs', 'slug' => 'organizers.access', 'module' => 'organizers', 'action' => 'access', 'user_type_id' => $clientTypeId],
        ];

        foreach ($adminPrivileges as $privilege) {
            Privilege::updateOrCreate(
                ['slug' => $privilege['slug']],
                $privilege
            );
        }

        foreach ($clientPrivileges as $privilege) {
            Privilege::updateOrCreate(
                ['slug' => $privilege['slug']],
                $privilege
            );
        }
    }

    /**
     * Créer les rôles système
     */
    private function createSystemRoles(): void
    {
        $adminTypeId = \App\Models\UserType::where('name', 'admin')->first()->id;
        $clientTypeId = \App\Models\UserType::where('name', 'client')->first()->id;

        $roles = [
            // Rôles Administrateur
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Accès complet au système avec tous les privilèges',
                'type' => Role::TYPE_SYSTEM,
                'user_type_id' => $adminTypeId,
                'level' => 100,
            ],
            [
                'name' => 'Admin',
                'slug' => Role::ADMIN,
                'description' => 'Administrateur avec accès aux fonctions principales',
                'type' => Role::TYPE_SYSTEM,
                'user_type_id' => $adminTypeId,
                'level' => 90,
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Support technique avec accès limité à l\'administration',
                'type' => Role::TYPE_SYSTEM,
                'user_type_id' => $adminTypeId,
                'level' => 80,
            ],

            // Rôles Client
            [
                'name' => 'Organisateur',
                'slug' => Role::ORGANIZER,
                'description' => 'Peut créer et gérer des événements',
                'type' => Role::TYPE_SYSTEM,
                'user_type_id' => $clientTypeId,
                'level' => 50,
            ],
            [
                'name' => 'Client',
                'slug' => Role::CLIENT,
                'description' => 'Utilisateur client standard',
                'type' => Role::TYPE_SYSTEM,
                'user_type_id' => $clientTypeId,
                'level' => 10,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }

    /**
     * Assigner les privilèges aux rôles
     */
    private function assignPrivilegesToRoles(): void
    {
        $adminTypeId = \App\Models\UserType::where('name', 'admin')->first()->id;
        $clientTypeId = \App\Models\UserType::where('name', 'client')->first()->id;

        // Super Admin - Tous les privilèges admin
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $allAdminPrivileges = Privilege::where('user_type_id', $adminTypeId)->get();
        $superAdminRole->privileges()->sync($allAdminPrivileges->pluck('id'));

        // Admin - Privilèges admin principaux (sauf paramètres système critiques)
        $adminRole = Role::where('slug', Role::ADMIN)->first();
        $adminPrivileges = Privilege::where('user_type_id', $adminTypeId)
            ->whereNotIn('slug', ['admin.settings']) // Exclure paramètres système
            ->get();
        $adminRole->privileges()->sync($adminPrivileges->pluck('id'));

        // Support - Privilèges admin limités (lecture principalement)
        $supportRole = Role::where('slug', 'support')->first();
        $supportPrivileges = Privilege::where('user_type_id', $adminTypeId)
            ->whereIn('slug', [
                'admin.access',
                'admin.reports',
                'admin.logs',
                'admin.users.view',
                'admin.organizers.view',
                'admin.events.view',
                'admin.payments.view',
            ])
            ->get();
        $supportRole->privileges()->sync($supportPrivileges->pluck('id'));

        // Organisateur - Privilèges clients étendus (gestion événements et billets)
        $organizerRole = Role::where('slug', Role::ORGANIZER)->first();
        $organizerPrivileges = Privilege::where('user_type_id', $clientTypeId)->get();
        $organizerRole->privileges()->sync($organizerPrivileges->pluck('id'));

        // Client - Privilèges clients de base (achat de billets)
        $clientRole = Role::where('slug', Role::CLIENT)->first();
        $clientPrivileges = Privilege::where('user_type_id', $clientTypeId)
            ->whereIn('slug', [
                'auth.access',
                'events.view',
                'orders.view', 'orders.create',
                'tickets.view',
                'payments.view', 'payments.process',
            ])
            ->get();
        $clientRole->privileges()->sync($clientPrivileges->pluck('id'));
    }
}