<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    /**
     * Get the collection of orders for the export
     */
    public function collection()
    {
        return Order::with(['buyer', 'tickets.event', 'tickets.ticketType'])
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
            'Client',
            'Email',
            'Événement',
            'Nb Billets',
            'Montant Total (XAF)',
            'Frais (XAF)',
            'Taxes (XAF)',
            'Net Organisateur (XAF)',
            'Statut',
            'Mode Paiement'
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($order): array
    {
        $firstTicket = $order->tickets->first();
        $event = $firstTicket ? $firstTicket->event : null;

        // Récupérer le paiement associé
        $payment = Payment::where('order_id', $order->id)->first();

        return [
            $order->reference,
            $order->created_at->format('d/m/Y H:i'),
            $order->buyer ? $order->buyer->name : $order->guest_name,
            $order->buyer ? $order->buyer->email : $order->guest_email,
            $event ? $event->title : 'N/A',
            $order->tickets->count(),
            number_format($order->total_amount, 0, ',', ' '),
            number_format($order->fees_amount ?? 0, 0, ',', ' '),
            number_format($order->tax_amount ?? 0, 0, ',', ' '),
            number_format($order->subtotal_amount ?? 0, 0, ',', ' '),
            $this->getStatusLabel($order->status),
            $payment ? strtoupper($payment->gateway) : 'N/A'
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
                    'startColor' => ['rgb' => '4F46E5']
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
        return 'Ventes ' . $this->startDate->format('d-m-Y') . ' au ' . $this->endDate->format('d-m-Y');
    }

    /**
     * Get status label in French
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'paid' => 'Payé',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            default => ucfirst($status)
        };
    }
}
