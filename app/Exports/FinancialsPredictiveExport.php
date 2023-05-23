<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialsPredictiveExport implements FromCollection, WithHeadings
{

    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            "Month",
            "Revenue (Actual)",
            "Revenue (Forecast)",
            "Actual Vs Forecast (Revenue)",
            "Avg. Rate (Actual)",
            "Avg. Rate (Forecast)",
            "Actual Vs Forecast (Average Rate)",
        ];
    }

}
