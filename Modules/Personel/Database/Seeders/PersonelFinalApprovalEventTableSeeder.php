<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Organisation\Entities\Organisation;

class PersonelFinalApprovalEventTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $position = DB::table('positions')->where("name", "Marketing Manager (MM)")->first();
        $religion = DB::table('religions')->where("name", "islam")->pluck("id")->first();
        Personel::firstOrCreate([
            "nik" => "01",
            "name" => "Agus Chandra",
            "position_id" => $position->id,
            "born_place" => "Gunung Kidul,",
            "born_date" => "1975-08-07",
            "religion_id" => $religion,
            "gender" => "L",
            "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
            "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
            "identity_card_type" => "1",
            "identity_number" => "12341234",
            "npwp" => "12341234",
            "blood_group" => "B negative",
        ]);
    }
}
