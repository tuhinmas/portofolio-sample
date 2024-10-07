<?php

namespace App\Exports;

use App\Traits\SalesOrderTrait;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\SalesOrder\Entities\SalesOrder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateFeeProduct implements FromCollection, WithHeadings,WithEvents
{
    use Exportable;
    use SalesOrderTrait;

    public function headings(): array
    {
        return [
            "Nama Produk",
            "Size",
            "Kategori Fee",
            "Periode Berlaku",
            "Kuartal",
            "Jumlah Min Unit",
            "Fee Per Unit",
        ];
    }

    public function collection()
    {
        return collect([
            [
                "Nama Produk" => "Big Phospor",
                "Size" => "250mL",
                "Kategori Fee" => "Reguler",
                "Periode Berlaku" => 2023,
                "Kuartal" => 2,
                "Jumlah Min Unit" => 10,
                "Fee Per Unit" => 500,
            ]
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet3 = $event->sheet;
                $objValidation3 = $sheet3->getCell('D2')->getDataValidation();
                $objValidation3->setType(DataValidation::TYPE_LIST);
                $objValidation3->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $objValidation3->setAllowBlank(true);
                $objValidation3->setShowInputMessage(true);
                $objValidation3->setShowErrorMessage(true);
                $objValidation3->setShowDropDown(true);
                $objValidation3->setErrorTitle('Input error');
                $objValidation3->setError('Value is not in list.');
                $objValidation3->setPromptTitle('Pick from list');
                $objValidation3->setPrompt('Please pick a value from the drop-down list.');
                $objValidation3->setFormula1('"Reguler, Target"');
                $objValidation3->setSqref('C2:C1048576');
            }
        ];
    }

}
