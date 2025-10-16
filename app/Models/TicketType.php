<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'currency',
        'max_quantity',
        'min_quantity',
        'available_quantity',
        'sales_start',
        'sales_end',
        'status',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_quantity' => 'integer',
        'min_quantity' => 'integer',
        'available_quantity' => 'integer',
        'sales_start' => 'datetime',
        'sales_end' => 'datetime',
        'sort_order' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['capacity'];

    /**
     * Get the event that owns the ticket type.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Get capacity attribute for frontend compatibility.
     */
    public function getCapacityAttribute()
    {
        return $this->available_quantity ?? $this->max_quantity ?? 0;
    }

    /**
     * Get the tickets for this ticket type.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_type_id');
    }

    /**
     * Get ticket prices for this ticket type.
     */
    public function ticketPrices(): HasMany
    {
        return $this->hasMany(TicketPrice::class, 'ticket_type_id');
    }

    /**
     * Get the price for a specific schedule and/or venue.
     * Returns the most specific price match based on priority.
     *
     * @param int|null $scheduleId
     * @param int|null $venueId
     * @param string|null $date Date for validity check
     * @return float
     */
    public function getPriceFor(?int $scheduleId = null, ?int $venueId = null, ?string $date = null): float
    {
        // Si l'événement n'utilise pas la tarification variable, retourner le prix de base
        if (!$this->event->use_variable_pricing) {
            return $this->price;
        }

        // Rechercher le prix le plus spécifique
        $prices = $this->ticketPrices()
            ->active()
            ->validAt($date)
            ->where(function ($query) use ($scheduleId, $venueId) {
                // Prix exact (schedule + venue)
                if ($scheduleId && $venueId) {
                    $query->orWhere(function ($q) use ($scheduleId, $venueId) {
                        $q->where('schedule_id', $scheduleId)
                          ->where('venue_id', $venueId);
                    });
                }

                // Prix par schedule
                if ($scheduleId) {
                    $query->orWhere(function ($q) use ($scheduleId) {
                        $q->where('schedule_id', $scheduleId)
                          ->whereNull('venue_id');
                    });
                }

                // Prix par venue
                if ($venueId) {
                    $query->orWhere(function ($q) use ($venueId) {
                        $q->where('venue_id', $venueId)
                          ->whereNull('schedule_id');
                    });
                }

                // Prix par défaut (ni schedule ni venue)
                $query->orWhere(function ($q) {
                    $q->whereNull('schedule_id')
                      ->whereNull('venue_id');
                });
            })
            ->get();

        // Si aucun prix trouvé, retourner le prix de base
        if ($prices->isEmpty()) {
            return $this->price;
        }

        // Trouver le prix avec la plus haute spécificité
        $bestPrice = $prices->sortByDesc(function ($price) {
            return $price->specificity_level;
        })->first();

        return $bestPrice->price;
    }

    /**
     * Scope a query to only include active ticket types.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include available ticket types.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                     ->where(function ($q) {
                         $q->whereNull('sales_start')
                           ->orWhere('sales_start', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('sales_end')
                           ->orWhere('sales_end', '>=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('available_quantity')
                           ->orWhere('available_quantity', '>', 0);
                     });
    }

    /**
     * Check if ticket type is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if ticket type is available for sale.
     */
    public function isAvailable(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check sales period
        if ($this->sales_start && $this->sales_start->isFuture()) {
            return false;
        }

        if ($this->sales_end && $this->sales_end->isPast()) {
            return false;
        }

        // Check quantity
        if ($this->available_quantity !== null && $this->available_quantity <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Get sold quantity.
     */
    public function getSoldQuantityAttribute(): int
    {
        return $this->tickets()->whereIn('status', ['issued', 'used'])->count();
    }

    /**
     * Get remaining quantity.
     */
    public function getRemainingQuantityAttribute(): ?int
    {
        if ($this->available_quantity === null) {
            return null;
        }

        return max(0, $this->available_quantity - $this->sold_quantity);
    }

    /**
     * Check if ticket type has quantity available.
     */
    public function hasQuantityAvailable(int $quantity = 1): bool
    {
        $remaining = $this->remaining_quantity;
        
        return $remaining === null || $remaining >= $quantity;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . ($this->currency ?? 'XAF');
    }
}
