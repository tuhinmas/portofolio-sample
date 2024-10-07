<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Religion;
use Modules\Organisation\Entities\Organisation;

class PersonelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('personels')->delete();
        $position = Position::inRandomOrder()->first();
        $religion = Religion::inRandomOrder()->first();
        $country = Country::inRandomOrder()->first();
        $organisation = Organisation::inRandomOrder()->first();
        Personel::create([
            "name" => "mastuhin",
            "position_id" => $position->id,
            "born_place" => "Gunung Kidul,",
            "born_date" => "1975-08-07",
            "religion_id" => $religion->id,
            "gender" => "L",
            "citizenship" => $country->id,
            "organisation_id" => $organisation->id,
            "identity_card_type" => "1",
            "identity_number" => "12341234",
            "npwp" => "12341234",
            "blood_group" => "B, negative",
        ]);
    }
}
