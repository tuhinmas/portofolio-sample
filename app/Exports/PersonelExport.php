<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Modules\Personel\Entities\Personel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Personel\Entities\PersonelExport as EntitiesPersonelExport;

class PersonelExport implements FromCollection, WithHeadings, WithMapping
{
    
    use Exportable;

    public function headings(): array
    {
        return ["name", "position name", "nik", "join_date", 
        "resign_date", "region_name", "sub_region_name"];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = EntitiesPersonelExport::all();

        return $data;
    }

    public function map($data): array
    {
        // Nama, Jabatan, no. Induk, tgl. Join, tgl. Resign, Group MDM (region), Group RMC(subregion)
        return [
            $data->name,
            $data->position_name,
            $data->nik,
            $data->join_date,
            $data->resign_date,
            $data->region_name,
            $data->sub_region_name,
        ];

    }
}
