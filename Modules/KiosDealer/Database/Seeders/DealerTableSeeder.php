<?php

namespace Modules\KiosDealer\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\DataAcuan\Entities\AgencyLevel;

class DealerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $user = User::where('name', 'administrator')->first();
        $agency_level = AgencyLevel::inRandomOrder()->first();

        $dealers = [
            'dealer_id' => 'cst-1236',
            'user_id' => $user->id,
            'name' => 'Toko Berkah Selalu',
            'address' => 'jl Banjar no. 45',
            'telephone' => '085956289255',
            'status' => 'accepted',
            'gmaps_link' => 'https://www.google.com/maps/place/Mou+Gelato+Yogyakarta/@-7.7650069,110.4090272,16.63z/data=!4m5!3m4!1s0x2e7a59505e37b8e5:0x2e11012fd29a7744!8m2!3d-7.7661722!4d110.4057888',
            'owner' => 'Mastuhin',
            'owner_address' => 'Jl. Lakbok np. 45',
            'owner_ktp' => '091451342985627',
            'owner_telephone' => '085123123123',
            'owner_npwp' => '085123123123',
            'agency_level_id' => $agency_level->id
        ];

        Dealer::Create($dealers);
    }
}
