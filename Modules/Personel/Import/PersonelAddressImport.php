<?php

namespace Modules\Personel\Import;

use App\Models\Address;
use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Str;

class PersonelAddressImport implements ToArray, WithHeadingRow
{
    use ResponseHandler;
    
    protected $data = [];

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

        Address::insert($dataImport);

        $this->data = [
            'insert_import' => count(($dataImport)),
            'failed_import' => $failed,
        ];
    }

    private function mapingData($value)
    {

        $rules = [
            'parent_id' => 'required',
            'type' => 'required',
            'detail_address' => 'required',
            'name' => 'required',
            'province_name' => 'required',
            'city_name' => 'required',
            'district_name' => 'required',
            'post_code' => 'required'
        ];

        $personel = Personel::where("name", $value['name'])->first();
        $personel_id = $personel->id ?? null; 

        $province = Province::whereRaw('LOWER(name) = ?', [strtolower($value["province_name"])])->first();
        $province_id = $province->id ?? null; 

        $city = City::whereRaw('LOWER(name) = ?', [strtolower($value["city_name"])])->first();
        $city_id = $city->id ?? null; 

        $district = District::whereRaw('LOWER(name) = ?', [strtolower($value["district_name"])])->first();
        $district_id = $district->id ?? null; 

        $country = Country::where("code", $value["country_code"])->first();
        $country_id = $country->id ?? null; 


        $requestData = new Request([
            'parent_id' => $personel_id,
            'type' => $value['type'],
            'detail_address' => $value['detail_address'],
            'province_name' => $province_id,
            'city_name' => $city_id,
            'district_name' => $district_id,
            'post_code' => $value["post_code"],
            'name' => $value["name"]
        ]);
        $requestData = $requestData->all();
        $validator = Validator::make($requestData, $rules);

        $findAddress = [
            'province_id' => $province_id,
            'city_id' => $city_id,
            'district_id' => $district_id,
            'country_id' => $country_id,
        ];

        $array = [
            'parent_id' => $personel_id, // ID personel yang berkaitan
            'type' => $value['type'],
            'detail_address' => $value['detail_address'],
            'post_code' => $value['post_code'],
            'gmaps_link' => $value['gmaps_link'],
            'id' => (string) Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $array = array_merge($array, $findAddress);

        if ($validator->fails()) {
            return false;
        }else{
            return $array;
        }

    }

    public function getData(): array
    {
        return $this->data;
    }


}
