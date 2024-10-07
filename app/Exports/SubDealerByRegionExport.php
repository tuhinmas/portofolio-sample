<?php

namespace App\Exports;

use App\Traits\MarketingArea;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\KiosDealer\Entities\DealerExport;
use Modules\KiosDealer\Entities\SubDealerExport;

class SubDealerByRegionExport implements FromQuery, ShouldQueue, WithHeadings
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
                'id', 'personel_id', 'distributor_id', 'sub_dealer_id', 'prefix', 'name',
                'sufix', 'address', 'telephone', 'second_telephone',
                'status', 'status_color', 'gmaps_link',
                'owner', 'owner_address', 'owner_ktp', 'owner_npwp',
                'owner_telephone', 'email', 'entity_id', 'note',
                'status_fee', 'grading_id', 'dealer_id',
              
                'created_at',
                'updated_at',
                'deleted_at',
              
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
        // dd($district_id);
        /* get dealer by dstrict address */
        $sub_dealer_id = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->whereIn("district_id", $district_id)
            ->where("type", "sub_dealer")
            ->get()
            ->pluck("parent_id")
            ->toArray();
        // dd($sub_dealer_id);
        return SubDealerExport::query()->whereIn('id', $sub_dealer_id);
    }
}
