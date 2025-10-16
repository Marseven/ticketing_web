<?php

namespace App\Exports;

use App\Models\Payout;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PayoutsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    /**
     * Get the collection of payouts for the export
     */
    public function collection()
    {
        return Payout::with('organizer')
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
            'Référence',
            'Date',
            'Organisateur',
            'Montant (XAF)',
            'Gateway',
            'Numéro',
            'Type',
            'Statut',
            'Date traitement',
            'Automatique'
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($payout): array
    {
        return [
            $payout->reference,
            $payout->created_at->format('d/m/Y H:i'),
            $payout->organizer ? $payout->organizer->name : 'N/A',
            number_format($payout->amount, 0, ',', ' '),
            strtoupper($payout->gateway),
            $payout->payee_msisdn,
            $this->getTypeLabel($payout->payout_type),
            $this->getStatusLabel($payout->status),
            $payout->processed_at ? $payout->processed_at->format('d/m/Y H:i') : 'N/A',
            $payout->is_automatic ? 'Oui' : 'Non'
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
                    'startColor' => ['rgb' => 'F59E0B']
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
        return 'Payouts';
    }

    /**
     * Get type label in French
     */
    private function getTypeLabel(string $type): string
    {
        return match($type) {
            'withdrawal' => 'Retrait',
            'refund' => 'Remboursement',
            'cashback' => 'Cashback',
            default => ucfirst($type)
        };
    }

    /**
     * Get status label in French
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'success' => 'Réussi',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => ucfirst($status)
        };
    }
}
