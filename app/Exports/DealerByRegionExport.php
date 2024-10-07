<?php

namespace App\Exports;

use App\Traits\MarketingArea;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\KiosDealer\Entities\DealerExport;

class DealerByRegionExport implements FromQuery, ShouldQueue, WithHeadings
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
                'id', 'personel_id', 'dealer_id', 'prefix', 'name',
                'sufix', 'address', 'telephone', 'second_telephone',
                'status', 'status_color', 'is_distributor', 'gmaps_link',
                'owner', 'owner_address', 'owner_ktp', 'owner_npwp',
                'owner_telephone', 'agency_level_id', 'entity_id', 'grading_id', 'note',
                'request_grading', 'last_grading', 'bank_account_number',
                'bank_account_name', 'bank_id', 'owner_bank_account_number',
                'owner_bank_account_name', 'owner_bank_id',
                'created_at',
                'updated_at',
                'deleted_at',
                'email',
                'status_fee',
                "personel_marketing_name",
                "grading_name",
                "district_id",
                "district_name",
                "city_id",
                "city_name",
                "province_id",
                "province_name",
                "owner_district_id",
                "owner_district_name",
                "owner_city_id",
                "owner_city_name",
                "owner_province_id",
                "owner_province_name",
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {

        /* get all district on this sub region */
        $district_id = $this->districtListByAreaId($this->region);

        /* get dealer by dstrict address */
        $dealer_id = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->whereIn("district_id", $district_id)
            ->where("type", "dealer")
            ->get()
            ->pluck("parent_id")
            ->toArray();
        return DealerExport::query()->whereIn('id', $dealer_id);
    }
}
