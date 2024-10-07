<?php

namespace Modules\Personel\Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;

class NewMarketingV3TableSeeder extends Seeder
{
    use HasRoles;
    public function __construct(Personel $personel, Position $position, Role $role){
        $this->personel = $personel;
        $this->position = $position;
        $this->role = $role;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('personels')->delete();
        $agama = DB::table('religions')->where('name','Islam')->first()->id;
        $country = DB::table('countries')->where('code','ID')->first()->id;
        $organisation = DB::table('organisations')->where('name','Javamas')->first()->id;
        
        Model::unguard();
        $pathToFile = 'Modules/Personel/Database/Seeders/csv/marketingV3.csv';
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
                if($key == 'id'){
                    $id = $value;
                }
                if($key == 'nik'){
                    $nik = $value;
                }
                if($key == 'name'){
                    $name = $value;
                }
                if($key == 'position'){
                    $position = $value;
                }
                if($key == 'marketing_area'){
                    $marketing_area = $value;
                }
            }
            $position = $this->position->where('name', $position)->first();
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
