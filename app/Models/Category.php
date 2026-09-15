<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'event_categories';
    
    protected $fillable = [
        'name',
        'slug', 
        'description',
        'color',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Expose un statut chaîne dérivé de is_active pour le front (Actif/Inactif).
    protected $appends = ['status'];

    /**
     * Statut lisible dérivé de is_active ('active' | 'inactive').
     */
    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Get the events for the category.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

}
