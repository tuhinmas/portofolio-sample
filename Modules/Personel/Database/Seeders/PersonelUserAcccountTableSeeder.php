<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\Personel;
use ogrrd\CsvIterator\CsvIterator;

class PersonelUserAcccountTableSeeder extends Seeder
{

    public function __construct(Personel $personel, User $user)
    {
        $this->personel = $personel;
        $this->user = $user;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/Personel/Database/Seeders/csv/personel_user_account.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            $row = (object) $row;
            if ($row->id) {
                $user = $this->user->where("personel_id", $row->id)->first();
                if (!$user) {
                    if ($row->email && $row->password) {
                        $user = $this->user->create([
                            "name" => $row->name,
                            "email" => $row->email,
                            "password" => bcrypt($row->password),
                            "personel_id" => $row->id,
                            "username" => $row->email,
                        ]);
                        $position = DB::table('positions')->where("id", $row->position_id)->first();
                        if ($position) {
                            $user->assignRole($position->name);
                        }
                    }
                }
            }
            else {
                $organisation_id = null;
                $supervisor_id = null;
                if ($row->organisation_id) {
                    $organisation_id = $row->organisation_id;
                }
                if ($row->supervisor_id) {
                    $supervisor_id = $row->supervisor_id;
                }

                $personel = $this->personel->firstOrCreate([
                    'name' => $row->name,
                    'nik' => $row->nik,
                    'born_date' => $row->born_date,
                    'born_place' => $row->born_place,
                    'supervisor_id' => $supervisor_id,
                    'position_id' => $row->position_id,
                    'religion_id' => $row->religion_id,
                    'gender' => $row->gender,
                    'citizenship' => $row->citizenship,
                    'organisation_id' => $supervisor_id,
                    'identity_card_type' => $row->identity_card_type,
                    'identity_number' => $row->identity_number,
                    'npwp' => $row->npwp,
                    'blood_group' => $row->blood_group,
                    'photo' => $row->photo,
                    'join_date' => $row->join_date,
                    'resign_date' => $row->resign_date,
                ]);

                if ($personel->wasRecentlyCreated === true) {
                    if ($row->email && $row->password) {
                        $user = $this->user->create([
                            "name" => $personel->name,
                            "email" => $row->email,
                            "password" => bcrypt($row->password),
                            "personel_id" => $personel->id,
                            "username" => $row->email,
                        ]);

                        $position = DB::table('positions')->where("id", $row->position_id)->first();
                        if ($position) {
                            $user->assignRole($position->name);
                        }
                    }
                }   
            }
        }
    }
}
