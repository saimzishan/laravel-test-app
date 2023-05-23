<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductivityPredictiveExport implements FromCollection, WithHeadings
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
            "Available Time",
            "Worked Time",
            "Billed Time (Actual)",
            "Billed Time (Target)",
            "Actual Vs Target (Billed Time)",
            "Billed Time (Forecast)",
            "Actual Vs Forecast (Billed Time)",
            "Collected Time",
        ];
    }

}
