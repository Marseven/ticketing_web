<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroBanner extends Model
{
    protected $fillable = [
        'title',
        'type',
        'media_url',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Scope query pour récupérer uniquement les banners actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query pour récupérer les banners triés par ordre d'affichage
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Accesseur pour l'URL du média - retourne l'URL complète
     * Transforme les chemins relatifs en URLs complètes
     */
    public function getMediaUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Si c'est déjà une URL complète (externe), on la retourne telle quelle
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Si c'est un chemin relatif (local), on construit l'URL complète
        // Ex: /storage/hero_banners/xxx.jpg -> https://primea.ga/storage/hero_banners/xxx.jpg
        if (str_starts_with($value, '/storage/') || str_starts_with($value, 'storage/')) {
            return url($value);
        }

        return $value;
    }
}
