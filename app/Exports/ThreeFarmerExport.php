<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Modules\Personel\Entities\Personel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\KiosDealerV2\Entities\StoreThreeFarmerExport;

class ThreeFarmerExport implements FromCollection, WithHeadings, WithMapping
{
    
    use Exportable;

    public function headings(): array
    {
        return ["name", "jabatan", "jumlah kios", "kios 3 petani"];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = StoreThreeFarmerExport::all();

        return $data;
    }

    public function map($data): array
    {
        // Nama, jabatan, jml kios, kios 3petani
        return [
            $data->name,
            $data->position_name,
            $data->store_count,
            $data->store_three_farmer
        ];

    }
}
