<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Privilege extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Modules disponibles
    const MODULE_AUTH = 'auth';
    const MODULE_EVENTS = 'events';
    const MODULE_ORDERS = 'orders';
    const MODULE_PAYMENTS = 'payments';
    const MODULE_TICKETS = 'tickets';
    const MODULE_SCANNING = 'scanning';
    const MODULE_ADMIN = 'admin';
    const MODULE_ORGANIZERS = 'organizers';
    const MODULE_USERS = 'users';

    // Actions disponibles
    const ACTION_CREATE = 'create';
    const ACTION_READ = 'read';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_MANAGE = 'manage';
    const ACTION_VIEW = 'view';
    const ACTION_ACCESS = 'access';

    /**
     * Get the roles that have this privilege.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'privilege_role')
                    ->withTimestamps();
    }

    /**
     * Scope for active privileges only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific module.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get privilege display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->action) . ' ' . ucfirst($this->module);
    }

    /**
     * Get all available modules.
     */
    public static function getAvailableModules(): array
    {
        return [
            self::MODULE_AUTH => 'Authentification',
            self::MODULE_EVENTS => 'Événements',
            self::MODULE_ORDERS => 'Commandes',
            self::MODULE_PAYMENTS => 'Paiements',
            self::MODULE_TICKETS => 'Billets',
            self::MODULE_SCANNING => 'Scan',
            self::MODULE_ORGANIZERS => 'Organisateurs',
            self::MODULE_USERS => 'Utilisateurs',
            self::MODULE_ADMIN => 'Administration',
        ];
    }

    /**
     * Get all available actions.
     */
    public static function getAvailableActions(): array
    {
        return [
            self::ACTION_CREATE => 'Créer',
            self::ACTION_READ => 'Lire',
            self::ACTION_VIEW => 'Voir',
            self::ACTION_UPDATE => 'Modifier',
            self::ACTION_DELETE => 'Supprimer',
            self::ACTION_MANAGE => 'Gérer',
            self::ACTION_ACCESS => 'Accéder',
        ];
    }
}