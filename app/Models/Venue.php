<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'name',
        'city',
        'address',
        'geo_lat',
        'geo_lng',
    ];

    protected $casts = [
        'geo_lat' => 'decimal:8',
        'geo_lng' => 'decimal:8',
    ];

    /**
     * Get the organizer that owns the venue.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Get the events for the venue.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the full address attribute.
     */
    public function getFullAddressAttribute()
    {
        return $this->address . ', ' . $this->city;
    }

    /**
     * Scope a query to only include venues in a specific city.
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }
}