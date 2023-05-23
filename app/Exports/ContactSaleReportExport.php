<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ContactSaleReportExport implements FromCollection, WithHeadings, WithEvents
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
            "Id",
            "Firm Name",
            "Name",
            "Email",
            "Address",
            "Contact",
            "Integration",
            "Registeration",

        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = 'A1:H1'; // All headers
                $bodyRange = 'A1:H'.$event->sheet->getHighestRow();
                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => Border::BORDER_THIN
                        )
                    )
                );
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold( true );
                $event->sheet->getDelegate()->getStyle($bodyRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($bodyRange)->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $event->sheet->getDelegate()->getStyle($bodyRange)->getBorders()
                    ->getInside()
                    ->setBorderStyle(Border::BORDER_THIN);

            },
        ];
    }

}
