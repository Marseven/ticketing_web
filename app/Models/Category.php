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
        'parent_id',
    ];

    protected $casts = [];

    /**
     * Get the events for the category.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

}
