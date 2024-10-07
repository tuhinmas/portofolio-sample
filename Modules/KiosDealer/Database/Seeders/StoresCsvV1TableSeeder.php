<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;

class StoresCsvV1TableSeeder extends Seeder
{
    public function __construct(Personel $personel,Store $store){
        $this->personel = $personel;
        $this->store = $store;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('stores')->delete();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/kios.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();

        foreach ($rows as $row) {
            $id = $row['id'];
            $name = $row['name'];
            $marketing = $row['marketing'];
            $address = $row['address'];
            $telepon = $row['telepon'];
            $status = 'accepted';
            $personel = $this->personel->where('name','like','%'.$marketing)->first();
            if ($personel != null) {
                // dd($personel); 
                $this->store->create([
                    'personel_id' => $personel->id,
                    'name' => $name,
                    'address' => $address,
                    'telephone' => $telepon,
                    'gmaps_link' => null,
                    'status' => 'accepted',
                    'status_color' => '000000',
                    'agency_level_id' => DB::table('agency_levels')->where('name', 'R3')->first()->id
                ]);
            }  
        }
    }
}
