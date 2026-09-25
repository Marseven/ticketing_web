<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * La liste des billets vendus d'un événement.
 *
 * C'est le document qu'on emporte à l'entrée ou qu'on envoie à l'organisateur
 * après la soirée : qui a acheté quoi, à quel prix, et qui est entré.
 *
 * Deux numéros y figurent, et ce n'est pas une redondance : celui du COMPTE
 * (renseigné à l'inscription) et celui qui a PAYÉ. Ils diffèrent souvent — on
 * achète pour un proche, ou l'on règle depuis un autre téléphone — et c'est
 * précisément par le second qu'un acheteur retrouve son billet quand il
 * appelle.
 *
 * Le prix retenu est celui PAYÉ, pas le tarif courant : sur un événement à
 * tarification variable, ou dont une catégorie a été retirée depuis, le tarif
 * du jour ne dit rien de ce que le client a versé.
 */
class EventTicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(private Event $event)
    {
    }

    /**
     * Les billets réellement vendus.
     *
     * Les billets annulés sont écartés : ils ne correspondent à aucune place
     * occupée ni à aucun encaissement. Ceux déjà scannés restent, avec leur
     * heure d'entrée — c'est souvent l'information qu'on vient chercher.
     */
    public function collection(): Collection
    {
        return static::query($this->event)->get();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Ticket> */
    public static function query(Event $event)
    {
        return Ticket::query()
            ->where('event_id', $event->id)
            ->whereNotIn('status', ['void', 'cancelled'])
            // Tout ce que le document affiche est chargé d'un coup : sans cela,
            // une liste de mille billets déclenche des milliers de requêtes.
            ->with([
                'ticketType:id,name',
                'schedule:id,starts_at',
                'buyer:id,name,email,phone',
                'order:id,reference,guest_name,guest_email,guest_phone,currency',
                'order.items:id,order_id,ticket_type_id,unit_price',
                'order.payments:id,order_id,payer_phone',
            ])
            ->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            'Code du billet',
            'Catégorie',
            'Date',
            'Acheteur',
            'E-mail',
            'Téléphone du compte',
            'Téléphone de paiement',
            'Commande',
            'Prix payé',
            'Devise',
            'Origine',
            'Statut',
            'Acheté le',
            'Scanné le',
        ];
    }

    /**
     * @param  Ticket  $ticket
     */
    public function map($ticket): array
    {
        return [
            $ticket->code,
            $ticket->ticketType?->name ?? '—',
            $ticket->schedule?->starts_at?->format('d/m/Y H:i') ?? '—',
            $ticket->buyer?->name ?? $ticket->order?->guest_name ?? '—',
            $ticket->buyer?->email ?? $ticket->order?->guest_email ?? '—',
            $ticket->buyer?->phone ?? $ticket->order?->guest_phone ?? '—',
            self::payerPhone($ticket),
            $ticket->order?->reference ?? '—',
            $ticket->price_paid ?? 0,
            $ticket->order?->currency ?? 'XAF',
            self::origine($ticket),
            self::statut($ticket),
            $ticket->created_at?->format('d/m/Y H:i') ?? '—',
            $ticket->used_at?->format('d/m/Y H:i') ?? '—',
        ];
    }

    /** Le numéro qui a réglé, souvent différent de celui du compte. */
    public static function payerPhone(Ticket $ticket): string
    {
        return $ticket->order?->payments
            ?->firstWhere(fn ($paiement) => filled($paiement->payer_phone))
            ?->payer_phone ?? '—';
    }

    public static function origine(Ticket $ticket): string
    {
        return match ($ticket->ticket_source) {
            'physical' => 'Billet physique',
            'comped' => 'Invitation',
            default => 'En ligne',
        };
    }

    public static function statut(Ticket $ticket): string
    {
        return match ($ticket->status) {
            'used' => 'Entré',
            'issued' => 'Valide',
            'pending' => 'En attente de paiement',
            default => $ticket->status,
        };
    }

    public function title(): string
    {
        // Le nom de l'onglet est limité à 31 caractères par le format.
        return mb_substr('Billets - ' . $this->event->title, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
