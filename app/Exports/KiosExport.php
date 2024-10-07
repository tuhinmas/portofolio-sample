<?php

namespace App\Exports;

use App\Traits\MarketingArea;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\KiosDealer\Entities\DealerExport;

class KiosExport implements FromQuery, ShouldQueue, WithHeadings
{
    use Exportable;
    use MarketingArea;

    public function __construct(string $region)
    {
        $this->region = $region;
    }

    public function headings(): array
    {
        return [
            [
               
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {

        
    }
}
