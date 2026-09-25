<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class EventsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    /**
     * Get the collection of events for the export
     */
    public function collection()
    {
        // ⚠️ Cette requête chargeait `tickets` — TOUS les billets de TOUS les
        // événements de la période — pour n'en garder que deux comptages. Sur
        // une plateforme qui en vend des milliers, cela remplit la mémoire
        // avant même d'écrire une ligne : le processus est tué, et l'erreur
        // fatale échappe au journaliseur de Laravel. D'où un 500 sans la
        // moindre trace, constaté en production.
        //
        // Les comptages et la recette sont désormais calculés par la base,
        // qui sait le faire sans rien rapatrier. `map()` n'a plus aucune
        // requête à lancer : c'était trois de plus par événement.
        return Event::query()
            ->with(['organizer:id,name', 'category:id,name', 'venue:id,name,city'])
            ->withCount([
                'tickets as tickets_sold_count' => fn ($q) => $q->whereIn('status', ['issued', 'used']),
                'tickets as tickets_used_count' => fn ($q) => $q->where('status', 'used'),
            ])
            ->withSum('ticketTypes as total_capacity', 'available_quantity')
            ->addSelect([
                'revenue' => \App\Models\Order::query()
                    ->selectRaw('COALESCE(SUM(orders.total_amount), 0)')
                    ->join('tickets', 'tickets.order_id', '=', 'orders.id')
                    ->whereColumn('tickets.event_id', 'events.id')
                    ->where('orders.status', 'paid'),
            ])
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Define the headings
     */
    public function headings(): array
    {
        return [
            'Titre',
            'Organisateur',
            'Catégorie',
            'Lieu',
            'Ville',
            'Statut',
            'Date création',
            'Date publication',
            'Billets vendus',
            'Billets utilisés',
            'Capacité totale',
            'Taux remplissage (%)',
            'Revenus (XAF)'
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($event): array
    {
        // Tout vient déjà de la requête : aucune requête par ligne.
        $ticketsSold = (int) ($event->tickets_sold_count ?? 0);
        $ticketsUsed = (int) ($event->tickets_used_count ?? 0);

        $totalCapacity = (int) ($event->total_capacity ?? 0);
        $fillRate = $totalCapacity > 0 ? round(($ticketsSold / $totalCapacity) * 100, 2) : 0;

        $revenue = (float) ($event->revenue ?? 0);

        return [
            $event->title,
            $event->organizer ? $event->organizer->name : 'N/A',
            $event->category ? $event->category->name : 'N/A',
            $event->venue ? $event->venue->name : 'N/A',
            $event->venue ? $event->venue->city : 'N/A',
            $this->getStatusLabel($event->status),
            $event->created_at?->format('d/m/Y H:i') ?? '—',
            $event->published_at ? $event->published_at->format('d/m/Y H:i') : 'Non publié',
            $ticketsSold,
            $ticketsUsed,
            $totalCapacity ?: 'Illimité',
            $fillRate,
            number_format($revenue, 0, ',', ' ')
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    /**
     * Get the title for the sheet
     */
    public function title(): string
    {
        return 'Événements ' . $this->startDate->format('d-m-Y') . ' au ' . $this->endDate->format('d-m-Y');
    }

    /**
     * Get status label in French
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'draft' => 'Brouillon',
            'published' => 'Publié',
            'cancelled' => 'Annulé',
            'completed' => 'Terminé',
            default => ucfirst($status)
        };
    }
}
