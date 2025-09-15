<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'event_id',
        'ticket_type_id',
        'schedule_id',
        'buyer_id',
        'code',
        'status',
        'issued_at',
        'used_at',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'metadata',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
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
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    // Methods
    public function isValid(): bool
    {
        return $this->status === 'issued';
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function canBeScanned(): bool
    {
        return $this->isValid() && !$this->hasBeenScanned();
    }

    public function hasBeenScanned(): bool
    {
        return $this->checkins()->where('result', 'valid')->exists();
    }

    public function markAsUsed(): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public function generateQRCode(): string
    {
        if (empty($this->code)) {
            $this->code = $this->generateUniqueCode();
            $this->save();
        }
        return $this->code;
    }

    /**
     * Générer le contenu QR Code sécurisé EMVCO/AMA
     */
    public function generateSecureQRContent(): string
    {
        $qrService = new \App\Services\QRCodeService();
        return $qrService->generateTicketQRCode($this);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function getQRCodeUrl(): string
    {
        return route('api.tickets.validate', ['code' => $this->code]);
    }
}
