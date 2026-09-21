<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce qu'une commande a demandé : type de billet, date, quantité, prix.
 *
 * Les billets ne sont plus créés au moment de la commande mais au paiement
 * (cf. App\Services\TicketIssuer) : c'est cette ligne qui porte l'intention
 * entre les deux, et qui permet de retenir les places sans émettre de billet.
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'event_id',
        'ticket_type_id',
        'schedule_id',
        'unit_price',
        'qty',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'qty' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class, 'schedule_id');
    }
}
