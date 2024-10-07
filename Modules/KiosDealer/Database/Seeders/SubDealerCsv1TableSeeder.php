<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Modules\Address\Entities\Address;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\SubDealer;

class SubDealerCsv1TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/sub_dealer_seeder_csv_1.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            $row = (object) $row;
            $status_fee = DB::table('status_fee')->where("name", $row->status_fee)->first();
            $entity = DB::table('entities')->where("name", $row->entitas)->first();
            $personel = DB::table('personels')->where("name", $row->personel_name)->first()->id;
            $sub_dealer = SubDealer::firstOrCreate([
                "personel_id" => $row->personel_id,
                'sub_dealer_id' => $row->sub_dealer_id,
                'prefix' => $row->prefix,
                'name' => $row->name,
                'sufix' => $row->sufix,
                'telephone' => $row->telephone,
                'second_telephone' => $row->second_telephone,
                'owner_ktp' => $row->ktp,
                'gmaps_link' => $row->gmaps_link,
                'address' => $row->address,
                'owner' => $row->owner,
                'owner_address' => $row->owner_address,
                'owner_telephone' => $row->owner_telephone,
                'owner_npwp' => $row->npwp,
                "email" => $row->email,
                'entity_id' => $entity->id,
                'status' => "accepted",
                'status_color' => "000000",
                'status_fee' => $status_fee->id,
            ]);

            $address = Address::firstOrCreate([
                "type" => "sub_dealer",
                "parent_id" => $sub_dealer->id,
                "province_id" => $row->province_id,
                "city_id" => $row->city_id,
                "district_id" => $row->district_id,
            ]);

            $owner_address = Address::firstOrCreate([
                "type" => "sub_dealer_owner",
                "parent_id" => $sub_dealer->id,
                "province_id" => $row->owner_province_id,
                "city_id" => $row->owner_city_id,
                "district_id" => $row->owner_district_id ,
            ]);
        }
    }
}
