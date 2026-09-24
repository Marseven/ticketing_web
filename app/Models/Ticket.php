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
        'ticket_source',
        'batch_reference',
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

    /**
     * Billets au nom d'une personne.
     *
     * Le nom vit à deux endroits selon le parcours : sur le COMPTE quand
     * l'achat a été fait connecté, sur la COMMANDE quand il a été fait en
     * invité — et l'invité est le cas le plus courant. Ne regarder que le
     * compte ne trouvait donc presque rien.
     *
     * Chaque mot doit être présent, dans n'importe quel ordre : « MYANDA
     * ROSELINE » doit retrouver « Roseline Myanda ». Quatre mots suffisent, au
     * delà c'est du bruit.
     */
    public function scopeForName($query, ?string $name)
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (array_slice($words, 0, 4) as $word) {
            $query->where(function ($q) use ($word) {
                $q->whereHas('buyer', fn ($b) => $b->where('name', 'LIKE', "%{$word}%"))
                    ->orWhereHas('order.buyer', fn ($b) => $b->where('name', 'LIKE', "%{$word}%"))
                    ->orWhereHas('order', fn ($o) => $o->where('guest_name', 'LIKE', "%{$word}%"));
            });
        }

        return $query;
    }

    /**
     * Billets rattachés à un numéro de téléphone.
     *
     * Trois numéros peuvent désigner la même personne : celui du compte (KYC),
     * celui laissé à la commande, et celui qui a effectivement PAYÉ — un
     * acheteur règle souvent depuis le téléphone d'un proche. Les trois
     * doivent retrouver le billet, sinon le client appelle et l'on ne sait pas
     * répondre.
     *
     * La comparaison porte sur les huit derniers chiffres : les numéros sont
     * saisis avec ou sans indicatif, avec ou sans espaces, et un `LIKE` brut
     * sur la chaîne ne trouverait rien.
     */
    public function scopeForPhone($query, ?string $phone)
    {
        $digits = \App\Support\PhoneNumber::digits($phone);

        if ($digits === '') {
            return $query;
        }

        $tail = strlen($digits) >= 8 ? substr($digits, -8) : $digits;

        $buyerPhone = \App\Support\PhoneNumber::sqlDigits('phone');
        $guestPhone = \App\Support\PhoneNumber::sqlDigits('guest_phone');

        return $query->where(function ($q) use ($tail, $buyerPhone, $guestPhone) {
            $q->whereHas('buyer', fn ($b) => $b->whereRaw("{$buyerPhone} LIKE ?", ["%{$tail}"]))
                // Le compte est porté par le billet ou par la commande selon
                // le parcours : les deux doivent être interrogés.
                ->orWhereHas('order.buyer', fn ($b) => $b->whereRaw("{$buyerPhone} LIKE ?", ["%{$tail}"]))
                ->orWhereHas('order', fn ($o) => $o->whereRaw("{$guestPhone} LIKE ?", ["%{$tail}"]))
                ->orWhereHas('order.payments', fn ($p) => $p->where('payer_phone', 'LIKE', "%{$tail}"));
        });
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
