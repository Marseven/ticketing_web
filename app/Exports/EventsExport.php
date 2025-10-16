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
        return Event::with(['organizer', 'category', 'venue', 'tickets', 'ticketTypes'])
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
        $ticketsSold = $event->tickets()->whereIn('status', ['issued', 'used'])->count();
        $ticketsUsed = $event->tickets()->where('status', 'used')->count();

        // Calculer la capacité totale
        $totalCapacity = $event->ticketTypes->sum('available_quantity') ?: 0;
        $fillRate = $totalCapacity > 0 ? round(($ticketsSold / $totalCapacity) * 100, 2) : 0;

        // Calculer les revenus
        $revenue = $event->tickets()
            ->join('orders', 'tickets.order_id', '=', 'orders.id')
            ->where('orders.status', 'paid')
            ->sum('orders.total_amount');

        return [
            $event->title,
            $event->organizer ? $event->organizer->name : 'N/A',
            $event->category ? $event->category->name : 'N/A',
            $event->venue ? $event->venue->name : 'N/A',
            $event->venue ? $event->venue->city : 'N/A',
            $this->getStatusLabel($event->status),
            $event->created_at->format('d/m/Y H:i'),
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
