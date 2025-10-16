<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Payout;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    /**
     * Get the collection of summary data
     */
    public function collection()
    {
        $payments = Payment::where('status', 'success')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $orders = Order::where('status', 'paid')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $payouts = Payout::where('status', 'success')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $totalRevenue = $payments->sum('amount');
        $totalOrders = $orders->count();
        $totalFees = $orders->sum('fees_amount');
        $totalTaxes = $orders->sum('tax_amount');
        $totalPayouts = $payouts->sum('amount');
        $netProfit = $totalFees + $totalTaxes - $totalPayouts;

        return new Collection([
            ['Indicateur', 'Valeur (XAF)', 'Remarque'],
            ['', '', ''],
            ['REVENUS', '', ''],
            ['Revenus totaux (bruts)', number_format($totalRevenue, 0, ',', ' '), 'Montant payé par les clients'],
            ['Nombre de commandes', $totalOrders, 'Commandes payées'],
            ['Panier moyen', $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 0, ',', ' ') : '0', 'Revenue / Nb commandes'],
            ['', '', ''],
            ['FRAIS & TAXES', '', ''],
            ['Frais de service collectés', number_format($totalFees, 0, ',', ' '), '5% par transaction'],
            ['Taxes collectées (TVA)', number_format($totalTaxes, 0, ',', ' '), '18% TVA'],
            ['Total frais + taxes', number_format($totalFees + $totalTaxes, 0, ',', ' '), 'Revenus plateforme'],
            ['', '', ''],
            ['PAYOUTS ORGANISATEURS', '', ''],
            ['Total payé aux organisateurs', number_format($totalPayouts, 0, ',', ' '), 'Retraits effectués'],
            ['En attente de payout', number_format($totalRevenue - $totalPayouts - ($totalFees + $totalTaxes), 0, ',', ' '), 'Non encore versé'],
            ['', '', ''],
            ['RÉSULTAT NET', '', ''],
            ['Bénéfice net plateforme', number_format($netProfit, 0, ',', ' '), 'Frais + Taxes - Payouts'],
        ]);
    }

    /**
     * Define the headings
     */
    public function headings(): array
    {
        return [
            'RAPPORT FINANCIER - Période du ' . $this->startDate->format('d/m/Y') . ' au ' . $this->endDate->format('d/m/Y')
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
            3 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F2937']]],
            8 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F2937']]],
            12 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F2937']]],
            16 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F2937']]],
            17 => [
                'font' => ['bold' => true, 'size' => 13],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7']
                ]
            ],
        ];
    }

    /**
     * Get the title for the sheet
     */
    public function title(): string
    {
        return 'Résumé Financier';
    }
}
