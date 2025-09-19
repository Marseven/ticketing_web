<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'contact_email',
        'contact_phone',
        'status',
        'is_active',
        'verified_at',
        'social_media',
        'settings',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
        'social_media' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organizer_user', 'organizer_id', 'user_id')
                    ->withPivot(['role', 'permissions'])
                    ->withTimestamps();
    }

    public function balances(): HasMany
    {
        return $this->hasMany(OrganizerBalance::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function getUrlAttribute(): string
    {
        return config('app.url') . '/organizers/' . $this->slug;
    }

    public function getPublishedEventsCountAttribute(): int
    {
        return $this->events()->where('status', 'published')->count();
    }

    public function getTotalEvents(): int
    {
        return $this->events()->count();
    }

    public function getTotalRevenue(): float
    {
        return $this->orders()
                    ->where('status', 'paid')
                    ->sum('total_amount');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organizer) {
            if (empty($organizer->slug)) {
                $organizer->slug = Str::slug($organizer->name);
                
                // Ensure uniqueness
                $originalSlug = $organizer->slug;
                $counter = 1;
                while (static::where('slug', $organizer->slug)->exists()) {
                    $organizer->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($organizer) {
            if ($organizer->isDirty('name') && empty($organizer->slug)) {
                $organizer->slug = Str::slug($organizer->name);
                
                // Ensure uniqueness
                $originalSlug = $organizer->slug;
                $counter = 1;
                while (static::where('slug', $organizer->slug)->where('id', '!=', $organizer->id)->exists()) {
                    $organizer->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
