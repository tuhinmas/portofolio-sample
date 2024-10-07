<?php

namespace App\Exports;

use Modules\DataAcuan\Entities\Package;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

class PackageExport implements FromQuery
{
    use Exportable;

    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        return Package::query();
    }
}
