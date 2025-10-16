<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class FinancialExport implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    /**
     * Return multiple sheets
     */
    public function sheets(): array
    {
        return [
            new FinancialSummarySheet($this->startDate, $this->endDate),
            new SalesExport($this->startDate, $this->endDate),
            new PayoutsSheet($this->startDate, $this->endDate),
        ];
    }
}
