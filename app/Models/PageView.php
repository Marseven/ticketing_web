<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une page consultée.
 *
 * Voir la migration pour ce qui n'est PAS stocké : aucune adresse IP, aucune
 * donnée permettant d'identifier une personne. `visitor_hash` est une
 * empreinte qui change chaque jour, uniquement destinée à ne pas compter dix
 * fois la même visite.
 */
class PageView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'path',
        'visitor_hash',
        'referrer_source',
        'device',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
