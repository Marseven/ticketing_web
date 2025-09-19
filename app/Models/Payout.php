<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'reference',
        'external_reference',
        'gateway',
        'payment_system_name',
        'payee_msisdn',
        'amount',
        'payout_type',
        'status',
        'is_automatic',
        'shap_response',
        'shap_payout_id',
        'shap_transaction_id',
        'processed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_automatic' => 'boolean',
        'shap_response' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Relation avec l'organisateur
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Marquer le payout comme réussi
     */
    public function markAsSuccess(array $shapResponse): void
    {
        $this->update([
            'status' => 'success',
            'processed_at' => now(),
            'shap_response' => $shapResponse,
            'shap_payout_id' => $shapResponse['payout_id'] ?? null,
            'shap_transaction_id' => $shapResponse['transaction_id'] ?? null,
        ]);
    }

    /**
     * Marquer le payout comme échoué
     */
    public function markAsFailed(string $reason, ?array $shapResponse = null): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'failure_reason' => $reason,
            'shap_response' => $shapResponse,
        ]);
    }

    /**
     * Marquer le payout comme en cours de traitement
     */
    public function markAsProcessing(?array $shapResponse = null): void
    {
        $this->update([
            'status' => 'processing',
            'shap_response' => $shapResponse,
            'shap_payout_id' => $shapResponse['payout_id'] ?? null,
        ]);
    }

    /**
     * Obtenir le nom d'affichage du gateway
     */
    public function getGatewayDisplayNameAttribute(): string
    {
        return match($this->gateway) {
            'airtelmoney' => 'Airtel Money',
            'moovmoney' => 'Moov Money',
            default => ucfirst($this->gateway),
        };
    }

    /**
     * Obtenir le nom d'affichage du statut
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'success' => 'Réussi',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => ucfirst($this->status),
        };
    }

    /**
     * Obtenir le type de payout formaté
     */
    public function getPayoutTypeDisplayNameAttribute(): string
    {
        return match($this->payout_type) {
            'withdrawal' => 'Retrait',
            'refund' => 'Remboursement',
            'cashback' => 'Cashback',
            default => ucfirst($this->payout_type),
        };
    }

    /**
     * Scope pour les payouts automatiques
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Scope pour les payouts manuels
     */
    public function scopeManual($query)
    {
        return $query->where('is_automatic', false);
    }

    /**
     * Scope pour les payouts en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les payouts réussis
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Générer une référence unique
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'PAYOUT-' . strtoupper(substr(md5(uniqid()), 0, 10));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}