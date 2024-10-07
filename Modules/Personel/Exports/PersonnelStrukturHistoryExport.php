<?php

namespace Modules\Personel\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonnelStructureHistory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PersonnelStrukturHistoryExport implements FromCollection, WithHeadings, WithMultipleSheets, WithColumnFormatting
{
    public function sheets(): array
    {
        return [
            new PersonnelStrukturHistoryExport(),
            new PersonnelExport()
        ];
    }

    public function headings(): array
    {
        return ["Berlaku Sejak", "Berakhir Pada", "Nama Marketing", "Nama RMC","Nama Asst MDM","Nama MDM","Nama MM"];
    }

    public function collection()
    {
        return PersonnelStructureHistory::with([
            'personel',
            'rmc',
            'asstMdm',
            'mdm',
            'mm'
        ])->get()->map(function($q){
            $indonesiaStartDate = Carbon::parse($q->start_date)->timezone('Asia/Jakarta');
            $indonesiaEndDate = Carbon::parse($q->end_date)->timezone('Asia/Jakarta');

            return [
                "start_date" => $q->start_date ? Date::dateTimeToExcel($indonesiaStartDate) : null,
                "end_date" => $q->end_date ? Date::dateTimeToExcel($indonesiaEndDate) : null,
                "personel" => $q->personel->name,
                "rmc" => optional($q->rmc)->name,
                "asstMdm" => optional($q->asstMdm)->name,
                "mdm" => optional($q->mdm)->name,
                "mm" => optional($q->mm)->name,
            ];
        });
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }
}
