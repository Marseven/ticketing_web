<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRecurrenceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'frequency',
        'interval',
        'by_day',
        'by_month_day',
        'by_month',
        'count',
        'until',
        'exceptions',
    ];

    protected $casts = [
        'until' => 'datetime',
        'exceptions' => 'array',
        'interval' => 'integer',
        'count' => 'integer',
    ];

    /**
     * Get the event that owns this recurrence rule.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Get by_day as array.
     */
    public function getByDayArrayAttribute(): array
    {
        return $this->by_day ? explode(',', $this->by_day) : [];
    }

    /**
     * Get by_month_day as array.
     */
    public function getByMonthDayArrayAttribute(): array
    {
        return $this->by_month_day ? explode(',', $this->by_month_day) : [];
    }

    /**
     * Get by_month as array.
     */
    public function getByMonthArrayAttribute(): array
    {
        return $this->by_month ? explode(',', $this->by_month) : [];
    }

    /**
     * Set by_day from array.
     */
    public function setByDayFromArray(array $days): void
    {
        $this->by_day = implode(',', $days);
    }

    /**
     * Set by_month_day from array.
     */
    public function setByMonthDayFromArray(array $days): void
    {
        $this->by_month_day = implode(',', $days);
    }

    /**
     * Set by_month from array.
     */
    public function setByMonthFromArray(array $months): void
    {
        $this->by_month = implode(',', $months);
    }

    /**
     * Check if a date is excluded.
     */
    public function isDateExcluded(\DateTime $date): bool
    {
        if (!$this->exceptions) {
            return false;
        }

        $dateStr = $date->format('Y-m-d');
        return in_array($dateStr, $this->exceptions);
    }

    /**
     * Add date to exceptions.
     */
    public function addException(string $date): void
    {
        $exceptions = $this->exceptions ?? [];
        if (!in_array($date, $exceptions)) {
            $exceptions[] = $date;
            $this->exceptions = $exceptions;
            $this->save();
        }
    }

    /**
     * Remove date from exceptions.
     */
    public function removeException(string $date): void
    {
        $exceptions = $this->exceptions ?? [];
        $exceptions = array_filter($exceptions, fn($d) => $d !== $date);
        $this->exceptions = array_values($exceptions);
        $this->save();
    }
}
