<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'user_type_id',
        'is_active',
        'level',
        'permissions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
        'level' => 'integer',
    ];

    // Types de rôles
    const TYPE_SYSTEM = 'system';
    const TYPE_ORGANIZER = 'organizer';
    const TYPE_CUSTOM = 'custom';

    // Rôles système prédéfinis
    const ADMIN = 'admin';
    const ORGANIZER = 'organizer';
    const CLIENT = 'client';
    const VISITOR = 'visitor';

    /**
     * Get the users that belong to this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
                    ->withPivot(['assigned_at', 'assigned_by'])
                    ->withTimestamps();
    }

    /**
     * Get the privileges associated with this role.
     */
    public function privileges(): BelongsToMany
    {
        return $this->belongsToMany(Privilege::class, 'privilege_role')
                    ->withTimestamps();
    }

    /**
     * Get the user type this role belongs to.
     */
    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

    /**
     * Scope for active roles only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system roles.
     */
    public function scopeSystem($query)
    {
        return $query->where('type', self::TYPE_SYSTEM);
    }

    /**
     * Scope for organizer roles.
     */
    public function scopeOrganizer($query)
    {
        return $query->where('type', self::TYPE_ORGANIZER);
    }

    /**
     * Check if role is admin.
     */
    public function isAdmin(): bool
    {
        return $this->slug === self::ADMIN;
    }

    /**
     * Check if role is organizer.
     */
    public function isOrganizer(): bool
    {
        return $this->slug === self::ORGANIZER;
    }

    /**
     * Check if role is client.
     */
    public function isClient(): bool
    {
        return $this->slug === self::CLIENT;
    }

    /**
     * Check if role is visitor.
     */
    public function isVisitor(): bool
    {
        return $this->slug === self::VISITOR;
    }

    /**
     * Check if role has a specific privilege.
     */
    public function hasPrivilege(string $privilegeSlug): bool
    {
        return $this->privileges()->where('slug', $privilegeSlug)->exists();
    }

    /**
     * Check if role can access a specific module.
     */
    public function canAccessModule(string $module): bool
    {
        return $this->privileges()->where('module', $module)->exists();
    }

    /**
     * Get all privileges for a specific module.
     */
    public function getModulePrivileges(string $module): array
    {
        return $this->privileges()
                    ->where('module', $module)
                    ->pluck('action')
                    ->toArray();
    }
}