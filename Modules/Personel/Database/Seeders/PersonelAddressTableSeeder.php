<?php

namespace Modules\Personel\Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class PersonelAddressTableSeeder extends Seeder
{
    public function __construct(Address $address)
    {
        $this->address = $address;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $personels = DB::table('personels')->get();
        $country = DB::table('countries')->where('code', "id")->first();
        foreach ($personels as $personel) {
            $this->address->create([
                'parent_id' => $personel->id,
                'type' => 'Rumah',
                'detail_address' => 'Jl. Kaliurang No. 1001, Puncak Merapi, Sleman, DIY',
                'country_id' => $country->id,
                'gmaps_link' => 'https://www.google.com/maps/place/Kopi+Merapi+Volcano/@-7.5887583,110.4243454,18.56z/data=!4m5!3m4!1s0x2e7a6150c8685763:0x50fd3bc03ab00057!8m2!3d-7.5888829!4d110.4239312',
            ]);
        }
    }
}
