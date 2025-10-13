<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for this type.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'user_type_id');
    }

    /**
     * Scope for active types only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get display label.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: ucfirst($this->name);
    }
}
