<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkin extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'scanned_by',
        'device_id',
        'result',
        'scanned_at',
        'location_hint',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ticket for this checkin.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the user who scanned the ticket.
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    /**
     * Scope a query to only include valid scans.
     */
    public function scopeValid($query)
    {
        return $query->where('result', 'valid');
    }

    /**
     * Scope a query to only include duplicate scans.
     */
    public function scopeDuplicate($query)
    {
        return $query->where('result', 'duplicate');
    }

    /**
     * Scope a query to only include invalid scans.
     */
    public function scopeInvalid($query)
    {
        return $query->where('result', 'invalid');
    }

    /**
     * Scope a query for a specific device.
     */
    public function scopeByDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope a query for a specific scanner.
     */
    public function scopeByScanner($query, int $scannerId)
    {
        return $query->where('scanned_by', $scannerId);
    }

    /**
     * Check if scan is valid.
     */
    public function isValid(): bool
    {
        return $this->result === 'valid';
    }

    /**
     * Check if scan is duplicate.
     */
    public function isDuplicate(): bool
    {
        return $this->result === 'duplicate';
    }

    /**
     * Check if scan is invalid.
     */
    public function isInvalid(): bool
    {
        return $this->result === 'invalid';
    }
}