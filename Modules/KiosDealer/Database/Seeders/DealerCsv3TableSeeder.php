<?php

namespace Modules\KiosDealer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Modules\Address\Entities\Address;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;

class DealerCsv3TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/dealer_seeder_csv_3.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach($rows as $row){
            $row = (object)$row;
            $status_fee = DB::table('status_fee')->where("name", $row->status_fee)->first();
            $grading = DB::table('gradings')->where("name", $row->grading)->first();
            $agency_level = DB::table('agency_levels')->where("name", $row->agency_level)->first();
            $entity = DB::table('entities')->where("name", $row->entitas)->first();
            // dd($row->personel_id);
            $dealer = Dealer::firstOrCreate([
                "personel_id" => $row->personel_id,
                'dealer_id' => $row->dealer_id,
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
                'agency_level_id' => $agency_level->id,
                "email" => $row->email,
                'entity_id' => $entity->id,
                'status' => "accepted",
                'status_color' => "000000",
                'status_fee' => $status_fee->id,
                'grading_id' => $grading->id,
                "bank_account_number" => $row->bank_account_number,
                "bank_name" => $row->bank_name,
            ],[
                'last_grading' => Carbon::now(),
            ]);

            $address = Address::firstOrCreate([
                "type" => "dealer",
                "parent_id" => $dealer->id,
                "province_id" => $row->province_id,
                "city_id" => $row->city_id,
                "district_id" => $row->district_id
            ]);
           
            $address = Address::firstOrCreate([
                "type" => "dealer_owner",
                "parent_id" => $dealer->id,
                "province_id" => $row->owner_province_id,
                "city_id" => $row->owner_city_id,
                "district_id" => $row->owner_district_id
            ]);
        }
    }
}
