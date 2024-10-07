<?php

namespace Modules\KiosDealer\Import;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Product;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerDeliveryAddress;

class DeliveryAddressImport implements ToArray, WithStartRow
{
    protected $data = [];

    public function startRow(): int
    {
        return 2;
    }

    public function array(array $row)
    {
        $failed = [];
        $dataImport = [];

        foreach ($row as $key => $value) {
            $data = $this->mapingData($value);
            if (!$data) {
                $failed[] = $value;
            }else{
                $dataImport[] = $data;
            }
        }

        //bulk inserts
        DealerDeliveryAddress::insert($dataImport);

        $this->data = [
            'Insert import' => count(($dataImport)),
            'Failed import' => $failed,
        ];
    }

    private function mapingData($value)
    {
        $explodeDealerId = explode(" - ", $value[0]);

        $array = [
            "dealer_id" => Dealer::where('dealer_id', ($explodeDealerId[1] ?? ''))->first()->id ?? null,
            'name' => $value[1],
            'address' => $value[2],
            'telephone' => $value[3],
            'gmaps_link' => $value[4],
            'longitude' => $value[5],
            'latitude' => $value[6],
        ];

        $array = array_merge($array, $this->findAddress($value[2]));

        $rules = [
            "dealer_id" => "required",
            "name" => "required",
            "telephone" => "required",
        ];

        $requestData = new Request($array);
        $requestData = $requestData->all();
        $validator = Validator::make($requestData, $rules);
        if ($validator->fails()) {
            return false;
        }else{
            return array_merge($requestData, [
                'id' => (string) Str::uuid(),
                'created_at' => date('Y-m-d H:i:s'),
                'is_active' => 1
            ]);
        }
    }

    private function findAddress($address = '')
    {
        $disctrictId = null;
        $cityId = null;
        $provinceId = null;

        $explodeAddress = explode(", ", ($address ?? ''));
        switch (count($explodeAddress)) {
            case 4:
                $findDistrict = District::where('name', $explodeAddress[1])
                    ->whereHas('city', function($q) use($explodeAddress){
                        $q->where('name', 'like','%'.$explodeAddress[2].'%');
                    })
                    ->first();
                
                    $disctrictId = $findDistrict->id ?? null;
                    $cityId = !empty($findDistrict->city) ? $findDistrict->city->id : null;
                    $provinceId = !empty($findDistrict->city->province) ? $findDistrict->city->province->id : null;
                break;

            case 3:
                $findCity = City::where('name', $explodeAddress[2])
                    ->whereHas('province', function($q) use($explodeAddress){
                        $q->where('name', 'like','%'.$explodeAddress[3].'%');
                    })
                    ->first();

                    $cityId = $findCity->id ?? null;
                    $provinceId = !empty($findCity->province) ? $findCity->province->id : null;
                break;
        }
        
        return [
            'district_id' => $disctrictId,
            'city_id' => $cityId,
            'province_id' => $provinceId,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

}