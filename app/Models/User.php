<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_organizer',
        'status',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'preferences',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_organizer' => 'boolean',
            'preferences' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the organizers associated with this user.
     */
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class, 'organizer_user', 'user_id', 'organizer_id')
                    ->withPivot(['role', 'permissions'])
                    ->withTimestamps();
    }

    /**
     * Get the orders placed by this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /**
     * Get the tickets owned by this user.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'buyer_id');
    }

    /**
     * Get the checkins performed by this user.
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class, 'scanned_by');
    }

    /**
     * Get the events created by this user.
     */
    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include organizers.
     */
    public function scopeOrganizers($query)
    {
        return $query->where('is_organizer', true);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is an organizer.
     */
    public function isOrganizer(): bool
    {
        return $this->is_organizer;
    }

    /**
     * Check if user has verified their email.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user has verified their phone.
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Get user's full name with fallback to email.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * Get user's initials.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Check if user can access organizer features for a specific organizer.
     */
    public function canAccessOrganizer(int $organizerId): bool
    {
        if (!$this->is_organizer) {
            return false;
        }

        return $this->organizers()->where('organizer_id', $organizerId)->exists();
    }

    /**
     * Get user's role for a specific organizer.
     */
    public function getRoleForOrganizer(int $organizerId): ?string
    {
        $organizer = $this->organizers()->where('organizer_id', $organizerId)->first();
        
        return $organizer?->pivot->role;
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withPivot(['assigned_at', 'assigned_by'])
                    ->withTimestamps();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has all of the given roles.
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->count() === count($roleSlugs);
    }

    /**
     * Check if user has a specific privilege.
     */
    public function hasPrivilege(string $privilegeSlug): bool
    {
        return $this->roles()
                    ->whereHas('privileges', function ($query) use ($privilegeSlug) {
                        $query->where('slug', $privilegeSlug);
                    })->exists();
    }

    /**
     * Check if user can perform action on module.
     */
    public function canPerform(string $action, string $module): bool
    {
        return $this->roles()
                    ->whereHas('privileges', function ($query) use ($action, $module) {
                        $query->where('action', $action)
                              ->where('module', $module);
                    })->exists();
    }

    /**
     * Get user's highest priority role.
     */
    public function getPrimaryRole(): ?Role
    {
        return $this->roles()->orderBy('level', 'desc')->first();
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Check if user can access admin area.
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->canPerform(Privilege::ACTION_ACCESS, Privilege::MODULE_ADMIN);
    }

    /**
     * Check if user can access organizer area.
     */
    public function canAccessOrganizerArea(): bool
    {
        return $this->isOrganizer() || 
               $this->hasRole(Role::ORGANIZER) || 
               $this->canPerform(Privilege::ACTION_ACCESS, Privilege::MODULE_ORGANIZERS);
    }

    /**
     * Get user access level (for routing).
     */
    public function getAccessLevel(): string
    {
        if ($this->canAccessAdmin()) {
            return 'admin';
        }
        
        if ($this->canAccessOrganizerArea()) {
            return 'organizer';
        }
        
        return 'client';
    }

    /**
     * Assign role to user.
     */
    public function assignRole(string $roleSlug, ?int $assignedBy = null): bool
    {
        $role = Role::where('slug', $roleSlug)->first();
        
        if (!$role) {
            return false;
        }

        if ($this->hasRole($roleSlug)) {
            return true;
        }

        $this->roles()->attach($role->id, [
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);

        return true;
    }

    /**
     * Remove role from user.
     */
    public function removeRole(string $roleSlug): bool
    {
        $role = Role::where('slug', $roleSlug)->first();
        
        if (!$role) {
            return false;
        }

        $this->roles()->detach($role->id);
        
        return true;
    }
}
