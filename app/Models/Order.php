<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'buyer_id',
        'currency',
        'subtotal_amount',
        'fees_amount',
        'tax_amount',
        'total_amount',
        'status',
        'reference',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organizer that owns the order.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
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
     * Get the payments for the order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    /**
     * Scope a query to only include completed orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
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
