<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;

class DealerAddressTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $dealer_address_detail = DB::table('address_with_details')->delete();
        $dealers = DB::table('dealers')->orderBy("name")->get();
        foreach ($dealers as $dealer) {
            $dealer_address = $dealer->address;
            $address_splited = explode(",", $dealer_address);
            $address = '';
            if (count($address_splited) >= 4) {
                for ($i = 0; $i < count($address_splited) - 3; $i++) {
                    $address = $address_splited[$i] . $address;
                    if ($i != count($address_splited) - 4) {
                        $address = $address . ",";
                    }
                };
                $district_name = $address_splited[count($address_splited) - 3];
                $city_name = $address_splited[count($address_splited) - 2];
                $province_name = $address_splited[count($address_splited) - 1];

                $province_name = $this->findProvince($province_name);
                if ($province_name) {
                    $city_name = $this->findCity($province_name->id, $city_name);
                    if ($city_name) {
                        $district_name = $this->findDistrict($city_name->id, $district_name);
                        if ($district_name) {
                            $this->addressDetail("dealer", $dealer->id, $province_name->id, $city_name->id, $district_name->id);
                            $dealer_update = Dealer::find($dealer->id);
                            $dealer_update->address = trim($address);
                            $dealer_update->save();
                        }
                    }
                }
            }

            /* owner address */
            $owner_address = $dealer->owner_address;
            $owner_address_splited = explode(",", $owner_address);
            $address_1 = '';
            if (count($owner_address_splited) >= 4) {
                for ($i = 0; $i < count($owner_address_splited) - 3; $i++) {
                    $address_1 = $address_1 . $owner_address_splited[$i];
                    if ($i != count($owner_address_splited) - 4) {
                        $address_1 = $address_1 . ",";
                    }
                };

                $district_name = $owner_address_splited[count($owner_address_splited) - 3];
                $city_name = $owner_address_splited[count($owner_address_splited) - 2];
                $province_name = $owner_address_splited[count($owner_address_splited) - 1];

                $province_name = $this->findProvince($province_name);
                if ($province_name) {
                    $city_name = $this->findCity($province_name->id, $city_name);
                    if ($city_name) {
                        $district_name = $this->findDistrict($city_name->id, $district_name);
                        if ($district_name) {
                            $this->addressDetail("dealer_owner", $dealer->id, $province_name->id, $city_name->id, $district_name->id);
                            $dealer_update = Dealer::find($dealer->id);
                            $dealer_update->owner_address = trim($address_1);
                            $dealer_update->save();
                        }
                    }
                }
            }
        }
    }

    /* find province by name */
    public function findProvince($name)
    {
        $name = trim($name);

        $province = DB::table('indonesia_provinces')->where("name", "like", "%" . $name . "%")->first();
        return $province;
    }

    /* find city by province id name name */
    public function findCity($province_id, $name)
    {
        $name = trim($name);
        $city = DB::table('indonesia_cities')->where("province_id", $province_id)->where("name", "like", "%" . $name . "%")->first();
        return $city;
    }

    public function findDistrict($city_id, $name)
    {
        $name = trim($name);
        $district = DB::table('indonesia_districts')->where("city_id", $city_id)->where("name", "like", "%" . $name . "%")->first();
        return $district;
    }

    public function addressDetail($type, $parent, $province, $city, $district)
    {
        $address_deatil = Address::firstOrCreate([
            "type" => $type,
            "parent_id" => $parent,
            "province_id" => $province,
            "city_id" => $city,
            "district_id" => $district,
        ]);
    }
}
