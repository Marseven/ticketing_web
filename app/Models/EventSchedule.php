<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'venue_id',
        'starts_at',
        'ends_at',
        'door_time',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'door_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the event that owns the schedule.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Get the venue for this schedule (if multi-venue event).
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    /**
     * Get ticket prices for this schedule.
     */
    public function ticketPrices()
    {
        return $this->hasMany(TicketPrice::class, 'schedule_id');
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include upcoming schedules.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * Check if schedule is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->starts_at > now();
    }

    /**
     * Check if schedule is past.
     */
    public function isPast(): bool
    {
        return $this->ends_at < now();
    }

    /**
     * Check if schedule is currently happening.
     */
    public function isHappening(): bool
    {
        $now = now();
        return $now >= $this->starts_at && $now <= $this->ends_at;
    }
}
