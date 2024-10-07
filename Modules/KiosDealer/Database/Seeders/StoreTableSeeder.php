<?php

namespace Modules\KiosDealer\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\AgencyLevel;

class StoreTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $personel = Personel::inRandomOrder(1)->first();
        $agency_level = AgencyLevel::inRandomOrder()->first();
        $stores = [
            [
                'name' => 'Toko berkah Makmur',
                'address' => 'Jl.  no. 32',
                'telephone' => '123123345345',
                'status' => 'filed',
                'personel_id' => $personel->id,
                'agency_level_id' => $agency_level->id
            ],
            [
                'name' => 'Toko Maju Makmur',
                'address' => 'Jl. Raya Banjar no. 32',
                'telephone' => '123123345345',
                'status' => 'filed',
                'personel_id' => $personel->id,
                'agency_level_id' => $agency_level->id
            ]
        ];
        foreach($stores as $store){
            Store::create($store);
        }
    }
}
