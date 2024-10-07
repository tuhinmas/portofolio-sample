<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;
use ogrrd\CsvIterator\CsvIterator;
use Spatie\Permission\Traits\HasRoles;

class MarketingKiosV4TableSeeder extends Seeder
{
    use HasRoles;
    public function __construct(Personel $personel, Position $position)
    {
        $this->personel = $personel;
        $this->position = $position;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $agama = DB::table('religions')->where('name', 'Islam')->first()->id;
        $country = DB::table('countries')->where('code', 'ID')->first()->id;
        $organisation = DB::table('organisations')->where('name', 'Javamas')->first()->id;

        Model::unguard();
        $pathToFile = 'Modules/Personel/Database/Seeders/csv/marketingV4TambahanKios.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            $id = null;
            $nik = null;
            $name = null;
            $position = null;
            $marketing_area = null;
            foreach ($row as $key => $value) {
                $id = $row['id'];
                $name = $row['name'];
            }
            $position = $this->position->where('name', 'Regional Marketing (RM)')->first();
            $personel = $this->personel->create([
                "id" => $id,
                "nik" => $nik,
                "name" => $name,
                "position_id" => $position->id,
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
            ]);
        }
    }
}
