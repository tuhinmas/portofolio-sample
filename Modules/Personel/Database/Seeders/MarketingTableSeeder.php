<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;

class MarketingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('personels')->delete();
        Model::unguard();
        DB::table('personels')->delete();
        $agama = DB::table('religions')->where('name', 'Islam')->first()->id;
        $country = DB::table('countries')->where('code', 'ID')->first()->id;
        $organisation = DB::table('organisations')->where('name', 'Javamas')->first()->id;
        $personnels = [
            [
                "name" => "Budi Kartika",
                "position_id" => DB::table('positions')->where('name','Manager Marketing(MM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Muh. Heru Susilo",
                "position_id" => DB::table('positions')->where('name','Manager District Marketing(MDM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Dede Jumaedi",
                "position_id" => DB::table('positions')->where('name','Manager District Marketing(MDM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Samsul Arifin",
                "position_id" => DB::table('positions')->where('name','Manager District Marketing(MDM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Teguh Setiawan",
                "position_id" => DB::table('positions')->where('name','Manager District Marketing(MDM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Heri Murdani K",
                "position_id" => DB::table('positions')->where('name','Manager District Marketing(MDM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            //assistent MDM
            [
                "name" => "Moh. Syaefudin Zuhri",
                "position_id" => DB::table('positions')->where('name','Assistant MDM')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Andi Fianto",
                "position_id" => DB::table('positions')->where('name','Assistant MDM')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Danang MH",
                "position_id" => DB::table('positions')->where('name','Assistant MDM')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            // RMC
            [
                "name" => "Athok Risdianto",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Alid Hermawan",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Dwi Totok Supriyadi",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Eko Mardiyanto",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Dwi Lanang T S",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Tasman",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Bambang Kusuma Wijaya",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Ahmad Rudi F",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Ngatiman",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Alex Sugianto",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Tian Pangloli",
                "position_id" => DB::table('positions')->where('name','Regional Marketing Coordinator (RMC)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            //RM
            [
                "name" => "Ilzam Nuzuli",
                "position_id" => DB::table('positions')->where('name','Regional Marketing (RM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Trisno Aji",
                "position_id" => DB::table('positions')->where('name','Regional Marketing (RM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Fajar Yuliyanto",
                "position_id" => DB::table('positions')->where('name','Regional Marketing (RM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Wendri muji A",
                "position_id" => DB::table('positions')->where('name','Regional Marketing (RM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Arista Wahyudiyanto ",
                "position_id" => DB::table('positions')->where('name','Regional Marketing (RM)')->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $agama,
                "gender" => "L",
                "citizenship" => $country,
                "organisation_id" => $organisation,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
        ];

        foreach ($personnels as $personnel) {
            Personel::create($personnel);
        }
    }
}
