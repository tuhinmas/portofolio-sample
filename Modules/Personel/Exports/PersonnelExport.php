<?php

namespace Modules\Personel\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Personel\Entities\Personel;

class PersonnelExport implements FromCollection, WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function headings(): array
    {
        return ["Nama", "Jabatan"];
    }

    public function title(): string
    {
        return "personel";
    }

    public function collection()
    {
        return Personel::withoutAppends()
            ->select('id','name','position_id')
            ->with(['position' => function($q){
                $q->select('id','name');
            }])
            ->whereHas('position', function($q){
                return $q->whereIn('name', marketing_positions())->where('name', '!=', 'aplikator');
            })
            ->where('status', 1)
            ->get()->map(function($q){
                return [
                    "name" => $q->name,
                    "position" => $q->position->name
                ];
            });

    }
}
