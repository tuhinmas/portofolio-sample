<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use ogrrd\CsvIterator\CsvIterator;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealer\Entities\CoreFarmer;

class StoreFromCsvPerMarketingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/kios_seeder.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            $row = (object) $row;
            if (!$row->personel_id) {
                continue;
            }
            $personel = Personel::find($$row->personel_id);
            if (!$row->personel_id) {
                continue;
            }
            $store = Store::firstOrCreate([
                'telephone' => $row->telephone,
                'second_telephone' => $row->second_telephone,
                'gmaps_link' => $row->gmaps_link,
                'name' => $row->name,
                'owner_name' => $row->owner_name,
                'address' => $row->address,
                'district_id' => $row->district_id,
                'city_id' => $row->city_id,
                'province_id' => $row->province_id,
                'personel_id' => $row->personel_id,
                'status' => "accepted",
                'status_color' => "000000",
            ]);

            $core_farmer = CoreFarmer::firstOrCreate([
                'telephone' => $row->farmer_telephone,
                'store_id' => $store->id,
                'name' => $row->farmer_name,
                'address' => $row->farmer_address,
            ]);
        }
    }
}
