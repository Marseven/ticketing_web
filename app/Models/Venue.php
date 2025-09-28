<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasImages;

class Venue extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'organizer_id',
        'name',
        'description',
        'city',
        'address',
        'postal_code',
        'country',
        'capacity',
        'phone',
        'email',
        'image',
        'image_url',
        'image_file',
        'status',
        'geo_lat',
        'geo_lng',
    ];

    protected $casts = [
        'geo_lat' => 'decimal:8',
        'geo_lng' => 'decimal:8',
        'capacity' => 'integer',
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

    /**
     * Méthodes pour le trait HasImages
     */
    protected function getImageType(): string
    {
        return 'venues';
    }

    protected function getImageUrlAttribute(): ?string
    {
        return $this->attributes['image_url'] ?? null;
    }

    protected function getImageFileAttribute(): ?string
    {
        return $this->attributes['image_file'] ?? null;
    }

    protected function setImageUrl(?string $url): void
    {
        $this->attributes['image_url'] = $url;
    }

    protected function setImageFile(?string $filename): void
    {
        $this->attributes['image_file'] = $filename;
    }
}