<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\CoreFarmer;

class CoreFarmerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $store = Store::inRandomOrder()->first();
        $core_farmers = [
            [
                'name' => 'Mastuhin',
                'telephone' => '085956289255',
                'address' => 'Ambarrukmo, Caturtinggal, Depok, Sleman, DIY',
                'store_id' => $store->id,
            ],
            [
                'name' => 'Steve K',
                'telephone' => '085956289255',
                'address' => 'Ambarrukmo, Caturtinggal, Depok, Sleman, DIY',
                'store_id' => $store->id,
            ],
            [
                'name' => 'Udin Salihin',
                'telephone' => '085956289255',
                'address' => 'Ambarrukmo, Caturtinggal, Depok, Sleman, DIY',
                'store_id' => $store->id,
            ],
        ];
        
        foreach($core_farmers as $core_farmer){
            CoreFarmer::create($core_farmer);
        }
    }
}
