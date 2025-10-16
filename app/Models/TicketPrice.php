<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_type_id',
        'schedule_id',
        'venue_id',
        'currency',
        'price',
        'valid_from',
        'valid_until',
        'priority',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * Get the ticket type that owns this price.
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    /**
     * Get the schedule that owns this price.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class, 'schedule_id');
    }

    /**
     * Get the venue that owns this price.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    /**
     * Scope a query to only include active prices.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include valid prices at a given date.
     */
    public function scopeValidAt($query, $date = null)
    {
        $date = $date ?? now();

        return $query->where('status', 'active')
                     ->where(function ($q) use ($date) {
                         $q->whereNull('valid_from')
                           ->orWhere('valid_from', '<=', $date);
                     })
                     ->where(function ($q) use ($date) {
                         $q->whereNull('valid_until')
                           ->orWhere('valid_until', '>=', $date);
                     });
    }

    /**
     * Scope pour trouver le prix pour un schedule spécifique.
     */
    public function scopeForSchedule($query, ?int $scheduleId)
    {
        if ($scheduleId) {
            return $query->where('schedule_id', $scheduleId);
        }
        return $query->whereNull('schedule_id');
    }

    /**
     * Scope pour trouver le prix pour un venue spécifique.
     */
    public function scopeForVenue($query, ?int $venueId)
    {
        if ($venueId) {
            return $query->where('venue_id', $venueId);
        }
        return $query->whereNull('venue_id');
    }

    /**
     * Check if this price is currently valid.
     */
    public function isValid(?string $date = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $checkDate = $date ? \Carbon\Carbon::parse($date) : now();

        if ($this->valid_from && $checkDate->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $checkDate->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get specificity level (pour le système de priorité).
     * Plus le niveau est élevé, plus le prix est spécifique.
     */
    public function getSpecificityLevelAttribute(): int
    {
        $level = 0;

        if ($this->schedule_id) {
            $level += 10;
        }

        if ($this->venue_id) {
            $level += 10;
        }

        return $level + $this->priority;
    }
}
