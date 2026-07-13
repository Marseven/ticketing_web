<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Logique UNIQUE de validation d'un billet au scan, partagée par tous les
 * points d'entrée (app mobile `POST /api/scans`, web `POST /tickets/validate`).
 *
 * Principe : on valide un billet par sa RÉFÉRENCE (code/QR), son ÉVÉNEMENT et
 * son STATUT — jamais via le compte utilisateur (les achats legacy sont
 * rattachés à un compte statique, donc non fiable métier). Le type de billet
 * (numérique/physique) est un simple attribut `ticket_source` : la même logique
 * s'applique aux deux, aucune duplication de cas particuliers.
 *
 * Retour standardisé (array) quel que soit le type :
 *   result         valid | duplicate | invalid | not_found | forbidden | error
 *   valid          bool (true seulement si result === 'valid')
 *   message        texte lisible pour l'agent de contrôle
 *   source         online | physical | comped | null   (nature réelle du billet)
 *   status_before  statut avant scan
 *   status_after   statut après scan
 *   first_scan     date du 1er scan (si duplicate)
 *   security_check PASSED | FALLBACK | NOT_FOUND
 *   ticket         array null-safe (infos billet) ou null
 *   checkin        Checkin créé ou null
 */
class TicketValidationService
{
    public function __construct(private QRCodeService $qr = new QRCodeService())
    {
    }

    /**
     * Valider (et marquer utilisé si OK) un billet à partir de la valeur scannée.
     *
     * @param  string  $scanned  Contenu brut du QR (format sécurisé EMVCO ou code simple)
     * @param  array   $ctx      Contexte du scan :
     *   scanned_by (int|null), device_id (string|null), scanned_at (string|null),
     *   location_hint (string|null), notes (string|null), metadata (array),
     *   enforce_organizer (bool), organizer_ids (iterable|null),
     *   enforce_schedule (bool)
     */
    public function validate(string $scanned, array $ctx = []): array
    {
        $ctx += [
            'scanned_by' => null, 'device_id' => null, 'scanned_at' => null,
            'location_hint' => null, 'notes' => null, 'metadata' => [],
            'enforce_organizer' => false, 'organizer_ids' => null,
            'enforce_schedule' => true,
        ];

        try {
            return DB::transaction(function () use ($scanned, $ctx) {
                // 1) Résolution du billet : QR sécurisé d'abord, sinon code simple.
                [$ticket, $security] = $this->resolve($scanned);

                if (!$ticket) {
                    $this->recordCheckin(null, 'invalid', $ctx, $scanned);
                    return $this->fail('not_found', 'QR code invalide ou billet introuvable', [
                        'security_check' => $security,
                    ]);
                }

                $ticket->loadMissing(['event', 'ticketType', 'buyer', 'schedule', 'order', 'event.venue']);

                // 2) Contrôle d'accès organisateur (mobile terrain). Optionnel.
                if ($ctx['enforce_organizer'] && $ctx['organizer_ids'] !== null) {
                    $orgIds = collect($ctx['organizer_ids']);
                    if (!$orgIds->contains($ticket->event?->organizer_id)) {
                        return $this->fail('forbidden', "Vous n'avez pas accès à cet événement.", [
                            'ticket' => $this->format($ticket), 'source' => $ticket->ticket_source,
                        ]);
                    }
                }

                // 3) Déjà scanné ? (anti double-scan)
                $existing = $ticket->checkins()->where('result', 'valid')->latest('scanned_at')->first();
                if ($existing) {
                    $this->recordCheckin($ticket, 'duplicate', $ctx, $scanned);
                    return $this->fail('duplicate', 'Ce billet a déjà été scanné le '
                        . $existing->scanned_at->format('d/m/Y à H:i:s'), [
                        'ticket' => $this->format($ticket),
                        'source' => $ticket->ticket_source,
                        'status_before' => $ticket->status,
                        'status_after' => $ticket->status,
                        'first_scan' => $existing->scanned_at->format('d/m/Y H:i:s'),
                        'security_check' => $security,
                    ]);
                }

                // 4) Statut scannable ? (issued)
                if (!$ticket->canBeScanned()) {
                    $this->recordCheckin($ticket, 'invalid', $ctx, $scanned);
                    return $this->fail('invalid', 'Billet non valide (statut : ' . $ticket->status . ')', [
                        'ticket' => $this->format($ticket),
                        'source' => $ticket->ticket_source,
                        'status_before' => $ticket->status,
                        'status_after' => $ticket->status,
                        'security_check' => $security,
                    ]);
                }

                // 5) Garde horaire : événement pas encore commencé (si connu).
                if ($ctx['enforce_schedule'] && $ticket->schedule && $ticket->schedule->starts_at?->isFuture()) {
                    $this->recordCheckin($ticket, 'invalid', $ctx, $scanned);
                    return $this->fail('invalid', "L'événement n'a pas encore commencé ("
                        . $ticket->schedule->starts_at->format('d/m/Y H:i') . ')', [
                        'ticket' => $this->format($ticket),
                        'source' => $ticket->ticket_source,
                        'status_before' => $ticket->status,
                        'status_after' => $ticket->status,
                        'security_check' => $security,
                    ]);
                }

                // 6) OK → marquer utilisé + journaliser le check-in valide.
                $before = $ticket->status;
                $ticket->update([
                    'status' => 'used',
                    'used_at' => $ctx['scanned_at'] ?? now(),
                ]);
                $checkin = $this->recordCheckin($ticket, 'valid', $ctx, $scanned);

                return [
                    'result' => 'valid',
                    'valid' => true,
                    'message' => 'Billet validé avec succès',
                    'source' => $ticket->ticket_source,
                    'status_before' => $before,
                    'status_after' => 'used',
                    'first_scan' => null,
                    'security_check' => $security,
                    'ticket' => $this->format($ticket),
                    'checkin' => $checkin,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Erreur validation billet', ['scanned' => $scanned, 'error' => $e->getMessage()]);
            return $this->fail('error', 'Erreur système lors de la validation');
        }
    }

    /**
     * Résoudre le billet : format QR sécurisé (EMVCO/AMA) d'abord, sinon code
     * simple. Retourne [Ticket|null, security_check].
     */
    private function resolve(string $scanned): array
    {
        try {
            $secure = $this->qr->validateTicketFromQRCode($scanned);
            if (!empty($secure['valid']) && !empty($secure['ticket'])) {
                return [$secure['ticket'], 'PASSED'];
            }
        } catch (\Throwable $e) {
            // Décodage sécurisé impossible → on tente le code simple.
        }

        $ticket = Ticket::byCode($scanned)->first();
        return [$ticket, $ticket ? 'FALLBACK' : 'NOT_FOUND'];
    }

    /**
     * Créer l'enregistrement de check-in (journal d'accès). N'écrit que les
     * colonnes réellement présentes dans la table `checkins`
     * (ticket_id, scanned_by, device_id, result, scanned_at, location_hint).
     *
     * La table impose ticket_id ET scanned_by NON NULS : on ne journalise donc
     * un check-in que pour un billet réel scanné par un utilisateur connu (un
     * QR introuvable ne crée pas de ligne — le résultat est tout de même
     * retourné à l'agent).
     */
    private function recordCheckin(?Ticket $ticket, string $result, array $ctx, string $scanned): ?Checkin
    {
        if (!$ticket || empty($ctx['scanned_by'])) {
            return null;
        }

        return Checkin::create([
            'ticket_id' => $ticket->id,
            'scanned_by' => $ctx['scanned_by'],
            'device_id' => $ctx['device_id'],
            'result' => $result,
            'scanned_at' => $ctx['scanned_at'] ?? now(),
            'location_hint' => $ctx['location_hint'],
        ]);
    }

    /** Construire une réponse d'échec standardisée. */
    private function fail(string $result, string $message, array $extra = []): array
    {
        return array_merge([
            'result' => $result,
            'valid' => false,
            'message' => $message,
            'source' => null,
            'status_before' => null,
            'status_after' => null,
            'first_scan' => null,
            'security_check' => $extra['security_check'] ?? null,
            'ticket' => null,
            'checkin' => null,
        ], $extra);
    }

    /**
     * Infos billet standardisées et NULL-SAFE (un billet physique/importé peut
     * ne pas avoir de type, d'acheteur, d'ordre ni de venue).
     */
    public function format(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'code' => $ticket->code,
            'status' => $ticket->status,
            'source' => $ticket->ticket_source,
            'batch_reference' => $ticket->batch_reference,
            'event' => $ticket->event ? [
                'id' => $ticket->event->id,
                'title' => $ticket->event->title,
                'slug' => $ticket->event->slug,
                'image_url' => $ticket->event->image,
                'venue_name' => $ticket->event->venue?->name ?? 'À définir',
            ] : null,
            'ticket_type' => $ticket->ticketType ? [
                'id' => $ticket->ticketType->id,
                'name' => $ticket->ticketType->name,
            ] : null,
            // 'holder' = nouvelle clé standardisée ; 'buyer' = alias rétro-compat
            // (l'app mobile et l'ancien /tickets/validate lisent 'buyer').
            'holder' => $holder = $ticket->buyer
                ? ['name' => $ticket->buyer->name, 'email' => $ticket->buyer->email]
                : ['name' => $ticket->order?->guest_name ?? '—', 'email' => $ticket->order?->guest_email ?? '—'],
            'buyer' => $holder,
            'schedule' => $ticket->schedule ? [
                'starts_at' => $ticket->schedule->starts_at?->format('d/m/Y H:i:s'),
                'ends_at' => $ticket->schedule->ends_at?->format('d/m/Y H:i:s'),
            ] : null,
            'issued_at' => $ticket->issued_at?->format('d/m/Y H:i:s'),
            'used_at' => $ticket->used_at?->format('d/m/Y H:i:s'),
        ];
    }
}
