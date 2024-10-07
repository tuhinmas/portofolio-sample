<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Seeder;
use ogrrd\CsvIterator\CsvIterator;
use Illuminate\Database\Eloquent\Model;

class MarketingSeederCsv5TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/sub_dealer_seeder_csv_1.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();

        // 'name' => $request->name,
        // 'born_date' => $request->born_date,
        // 'born_place' => $request->born_place,
        // 'supervisor_id' => $request->supervisor_id,
        // 'position_id' => $request->position_id,
        // 'religion_id' => $request->religion_id,
        // 'gender' => $request->gender,
        // 'citizenship' => $request->citizenship,
        // 'organisation_id' => $request->organisation_id,
        // 'identity_card_type' => $request->identity_card_type,
        // 'identity_number' => $request->identity_number,
        // 'npwp' => $request->npwp,
        // 'blood_group' => $blood_group,
        // 'photo' => $request->photo,
        // 'join_date' => $request->join_date,
        // 'resign_date' => $request->resign_date,
    }
}
