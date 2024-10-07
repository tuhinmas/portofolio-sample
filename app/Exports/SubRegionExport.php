<?php

namespace App\Exports;

use Modules\DataAcuan\Entities\SubRegion;
use Maatwebsite\Excel\Concerns\FromCollection;

class SubRegionExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return SubRegion::all();
    }
}
