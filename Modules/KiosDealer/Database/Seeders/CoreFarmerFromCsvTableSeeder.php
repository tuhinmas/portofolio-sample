<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\CoreFarmer;

class CoreFarmerFromCsvTableSeeder extends Seeder
{
    public function __construct(Store $store, CoreFarmer $core_farmer){
        $this->store = $store;
        $this->core_farmer = $core_farmer;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('core_farmers')->delete();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/petani.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            // dd($row);
            $kios = $row['kios'];
            $name = $row['core_farmer'];
            $telephone = $row['telepon'];
            $address = $row['address'];
            $store = $this->store->where('name','like','%' . $kios)->first();
            if($store != null){
                $this->core_farmer->create([
                    'name' => $name,
                    'telephone' => $telephone,
                    'address' => $address,
                    'store_id' => $store->id,
                ]);
            }
        }
    }
}
