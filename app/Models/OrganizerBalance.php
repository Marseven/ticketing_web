<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'gateway',
        'balance',
        'pending_balance',
        'phone_number',
        'auto_payout_enabled',
        'auto_payout_threshold',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'auto_payout_threshold' => 'decimal:2',
        'auto_payout_enabled' => 'boolean',
    ];

    /**
     * Relation avec l'organisateur
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Ajouter du solde (après un paiement réussi)
     */
    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Déduire du solde (après un payout)
     */
    public function deductBalance(float $amount): bool
    {
        if ($this->balance >= $amount) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Ajouter du solde en attente
     */
    public function addPendingBalance(float $amount): void
    {
        $this->increment('pending_balance', $amount);
    }

    /**
     * Convertir le solde en attente en solde disponible
     */
    public function confirmPendingBalance(float $amount): void
    {
        if ($this->pending_balance >= $amount) {
            $this->decrement('pending_balance', $amount);
            $this->increment('balance', $amount);
        }
    }

    /**
     * Vérifier si le payout automatique doit être déclenché
     */
    public function shouldTriggerAutoPayout(): bool
    {
        return $this->auto_payout_enabled 
            && $this->balance >= $this->auto_payout_threshold
            && $this->auto_payout_threshold > 0
            && !empty($this->phone_number);
    }

    /**
     * Obtenir le nom d'affichage du gateway
     */
    public function getGatewayDisplayNameAttribute(): string
    {
        return match($this->gateway) {
            'airtelmoney' => 'Airtel Money',
            'moovmoney' => 'Moov Money',
            'ORABANK_NG' => 'Visa/Mastercard',
            default => ucfirst($this->gateway),
        };
    }
}