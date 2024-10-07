<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\SalesOrderV2\Entities\SalesOrderExport;

class SalesOrderIndirectExport implements FromQuery, ShouldQueue, WithHeadings
{
    use Exportable;

    public function __construct(int $month, int $year)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function headings(): array
    {
        return [
            [
                'id',
                'type',
                'store_id',
                'personel_id',
                'distributor_id',
                'counter_id',
                'conuter_fee',
                'model',
                'agency_level_id',
                'payment_method_id',
                'recipient_phone_number',
                'delivery_location',
                'sub_total',
                'discount',
                'total',
                'status',
                'status_fee_id',
                'note',
                'reference_number',
                'proforma',
                'link',
                'date',
                'return',
                'created_at',
                'updated_at',
                'deleted_at',
                'personel_marketing_name',
                'delaer_name'
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        return SalesOrderExport::query()->where('type', '2')->whereYear("date", $this->year)
                ->whereMonth("date", $this->month)->orderBy("date", "desc");
    }
}
