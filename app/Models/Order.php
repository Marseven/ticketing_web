<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Durée pendant laquelle une commande en attente retient ses places.
     *
     * Au-delà, le paiement ne viendra plus : `CancelPendingOrders` l'annule.
     * Mais le DÉCOMPTE ne doit pas dépendre de ce ménage — si le cron traîne
     * ou tombe, des places resteraient bloquées indéfiniment et l'événement
     * s'afficherait complet alors qu'il reste de la place.
     */
    public const HOLD_MINUTES = 60;

    protected $fillable = [
        'organizer_id',
        'buyer_id',
        'currency',
        'subtotal_amount',
        'fees_amount',
        'commission_percentage',
        'tax_amount',
        'service_fee_amount',
        'service_fee_bearer',
        'total_amount',
        'status',
        'reference',
        'placed_at',
        'paid_at',
        'is_guest_order',
        'guest_name',
        'guest_email',
        'guest_phone',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_guest_order' => 'boolean',
    ];

    /**
     * Get the organizer that owns the order.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }

    /**
     * Get the buyer that owns the order.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the tickets for the order.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'order_id');
    }

    /**
     * Get the event related to this order through the first ticket.
     */
    /**
     * L'événement de la commande.
     *
     * ⚠️ Passe par les billets, qui n'existent qu'une fois la commande payée :
     * sur une commande en attente, utiliser `resolveEvent()` qui retombe sur la
     * ligne de commande.
     */
    public function event()
    {
        return $this->hasOneThrough(
            Event::class,
            Ticket::class,
            'order_id',  // Foreign key on tickets table
            'id',        // Foreign key on events table
            'id',        // Local key on orders table
            'event_id'   // Local key on tickets table
        );
    }

    /**
     * Get the payments for the order.
     */
    /**
     * Ce que la commande a demandé (type, date, quantité).
     *
     * Renseigné dès la commande, alors que les billets n'existent qu'une fois
     * le paiement confirmé : c'est cette ligne qui retient la place entre les
     * deux (cf. App\Services\TicketIssuer).
     */
    /**
     * Événement de la commande, qu'elle soit payée ou non : par les billets
     * s'ils existent, sinon par ce qui a été commandé.
     */
    public function resolveEvent(): ?Event
    {
        return $this->event ?? $this->items()->with('event')->first()?->event;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the total paid amount.
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'success')->sum('amount');
    }

    /**
     * Check if order is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->total_paid >= $this->total_amount;
    }

    /**
     * Check if order is expired.
     */
    public function isExpired(): bool
    {
        return false; // Pas d'expiration dans cette version du schéma
    }

    /**
     * Generate unique order reference.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}
