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
        $privileges = [
            // Authentification
            ['name' => 'Accès Authentification', 'slug' => 'auth.access', 'module' => 'auth', 'action' => 'access'],
            
            // Événements
            ['name' => 'Voir Événements', 'slug' => 'events.view', 'module' => 'events', 'action' => 'view'],
            ['name' => 'Créer Événements', 'slug' => 'events.create', 'module' => 'events', 'action' => 'create'],
            ['name' => 'Modifier Événements', 'slug' => 'events.update', 'module' => 'events', 'action' => 'update'],
            ['name' => 'Supprimer Événements', 'slug' => 'events.delete', 'module' => 'events', 'action' => 'delete'],
            ['name' => 'Gérer Événements', 'slug' => 'events.manage', 'module' => 'events', 'action' => 'manage'],
            
            // Commandes
            ['name' => 'Voir Commandes', 'slug' => 'orders.view', 'module' => 'orders', 'action' => 'view'],
            ['name' => 'Créer Commandes', 'slug' => 'orders.create', 'module' => 'orders', 'action' => 'create'],
            ['name' => 'Modifier Commandes', 'slug' => 'orders.update', 'module' => 'orders', 'action' => 'update'],
            ['name' => 'Gérer Commandes', 'slug' => 'orders.manage', 'module' => 'orders', 'action' => 'manage'],
            
            // Paiements
            ['name' => 'Voir Paiements', 'slug' => 'payments.view', 'module' => 'payments', 'action' => 'view'],
            ['name' => 'Traiter Paiements', 'slug' => 'payments.process', 'module' => 'payments', 'action' => 'create'],
            ['name' => 'Gérer Paiements', 'slug' => 'payments.manage', 'module' => 'payments', 'action' => 'manage'],
            
            // Billets
            ['name' => 'Voir Billets', 'slug' => 'tickets.view', 'module' => 'tickets', 'action' => 'view'],
            ['name' => 'Créer Billets', 'slug' => 'tickets.create', 'module' => 'tickets', 'action' => 'create'],
            ['name' => 'Valider Billets', 'slug' => 'tickets.validate', 'module' => 'tickets', 'action' => 'update'],
            ['name' => 'Gérer Billets', 'slug' => 'tickets.manage', 'module' => 'tickets', 'action' => 'manage'],
            
            // Scanning
            ['name' => 'Accès Scanning', 'slug' => 'scanning.access', 'module' => 'scanning', 'action' => 'access'],
            ['name' => 'Scanner Billets', 'slug' => 'scanning.scan', 'module' => 'scanning', 'action' => 'create'],
            
            // Organisateurs
            ['name' => 'Accès Organisateurs', 'slug' => 'organizers.access', 'module' => 'organizers', 'action' => 'access'],
            ['name' => 'Voir Organisateurs', 'slug' => 'organizers.view', 'module' => 'organizers', 'action' => 'view'],
            ['name' => 'Créer Organisateurs', 'slug' => 'organizers.create', 'module' => 'organizers', 'action' => 'create'],
            ['name' => 'Modifier Organisateurs', 'slug' => 'organizers.update', 'module' => 'organizers', 'action' => 'update'],
            ['name' => 'Supprimer Organisateurs', 'slug' => 'organizers.delete', 'module' => 'organizers', 'action' => 'delete'],
            
            // Utilisateurs
            ['name' => 'Voir Utilisateurs', 'slug' => 'users.view', 'module' => 'users', 'action' => 'view'],
            ['name' => 'Créer Utilisateurs', 'slug' => 'users.create', 'module' => 'users', 'action' => 'create'],
            ['name' => 'Modifier Utilisateurs', 'slug' => 'users.update', 'module' => 'users', 'action' => 'update'],
            ['name' => 'Supprimer Utilisateurs', 'slug' => 'users.delete', 'module' => 'users', 'action' => 'delete'],
            ['name' => 'Gérer Utilisateurs', 'slug' => 'users.manage', 'module' => 'users', 'action' => 'manage'],
            
            // Administration
            ['name' => 'Accès Administration', 'slug' => 'admin.access', 'module' => 'admin', 'action' => 'access'],
            ['name' => 'Voir Rapports', 'slug' => 'admin.reports', 'module' => 'admin', 'action' => 'view'],
            ['name' => 'Paramètres Système', 'slug' => 'admin.settings', 'module' => 'admin', 'action' => 'manage'],
            ['name' => 'Voir Logs', 'slug' => 'admin.logs', 'module' => 'admin', 'action' => 'view'],
        ];

        foreach ($privileges as $privilege) {
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
        $roles = [
            [
                'name' => 'Administrateur',
                'slug' => Role::ADMIN,
                'description' => 'Accès complet au système',
                'type' => Role::TYPE_SYSTEM,
                'level' => 100,
            ],
            [
                'name' => 'Organisateur',
                'slug' => Role::ORGANIZER,
                'description' => 'Peut créer et gérer des événements',
                'type' => Role::TYPE_SYSTEM,
                'level' => 50,
            ],
            [
                'name' => 'Client',
                'slug' => Role::CLIENT,
                'description' => 'Utilisateur client standard',
                'type' => Role::TYPE_SYSTEM,
                'level' => 10,
            ],
            [
                'name' => 'Visiteur',
                'slug' => Role::VISITOR,
                'description' => 'Accès public limité',
                'type' => Role::TYPE_SYSTEM,
                'level' => 1,
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
        // Admin - Tous les privilèges
        $adminRole = Role::where('slug', Role::ADMIN)->first();
        $allPrivileges = Privilege::all();
        $adminRole->privileges()->sync($allPrivileges->pluck('id'));

        // Organisateur - Privilèges liés aux événements et billets
        $organizerRole = Role::where('slug', Role::ORGANIZER)->first();
        $organizerPrivileges = Privilege::whereIn('slug', [
            'auth.access',
            'events.view', 'events.create', 'events.update', 'events.manage',
            'orders.view', 'orders.manage',
            'tickets.view', 'tickets.create', 'tickets.validate', 'tickets.manage',
            'scanning.access', 'scanning.scan',
            'organizers.access',
            'payments.view',
        ])->get();
        $organizerRole->privileges()->sync($organizerPrivileges->pluck('id'));

        // Client - Privilèges de base
        $clientRole = Role::where('slug', Role::CLIENT)->first();
        $clientPrivileges = Privilege::whereIn('slug', [
            'auth.access',
            'events.view',
            'orders.view', 'orders.create',
            'tickets.view',
            'payments.view', 'payments.process',
        ])->get();
        $clientRole->privileges()->sync($clientPrivileges->pluck('id'));

        // Visiteur - Privilèges publics uniquement
        $visitorRole = Role::where('slug', Role::VISITOR)->first();
        $visitorPrivileges = Privilege::whereIn('slug', [
            'events.view',
        ])->get();
        $visitorRole->privileges()->sync($visitorPrivileges->pluck('id'));
    }
}