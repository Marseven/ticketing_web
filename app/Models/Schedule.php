<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'event_schedules';

    protected $fillable = [
        'event_id',
        'starts_at',
        'ends_at',
        'door_time',
        'status',
        'max_capacity',
        'current_capacity',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'door_time' => 'datetime',
        'max_capacity' => 'integer',
        'current_capacity' => 'integer',
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
     * Get the tickets for this schedule.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'schedule_id');
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include future schedules.
     */
    public function scopeFuture($query)
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * Scope a query to only include current schedules.
     */
    public function scopeCurrent($query)
    {
        return $query->where('starts_at', '<=', now())
                     ->where('ends_at', '>=', now());
    }

    /**
     * Check if schedule is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if schedule is in the future.
     */
    public function isFuture(): bool
    {
        return $this->starts_at->isFuture();
    }

    /**
     * Check if schedule is currently running.
     */
    public function isCurrent(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * Check if schedule has ended.
     */
    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    /**
     * Check if doors are open.
     */
    public function areDoorsOpen(): bool
    {
        if (!$this->door_time) {
            return $this->isCurrent();
        }
        
        return now()->isAfter($this->door_time) && !$this->hasEnded();
    }

    /**
     * Get available capacity.
     */
    public function getAvailableCapacityAttribute(): int
    {
        return max(0, ($this->max_capacity ?? 0) - ($this->current_capacity ?? 0));
    }

    /**
     * Check if schedule has capacity.
     */
    public function hasCapacity(): bool
    {
        if (!$this->max_capacity) {
            return true;
        }
        
        return $this->available_capacity > 0;
    }

    /**
     * Get capacity percentage.
     */
    public function getCapacityPercentageAttribute(): float
    {
        if (!$this->max_capacity) {
            return 0;
        }
        
        return round(($this->current_capacity / $this->max_capacity) * 100, 1);
    }
}