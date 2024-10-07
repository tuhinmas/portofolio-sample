<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelStatusHistory;

class PersonelStatusHistoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Personel::query()
            ->get()
            ->each(function ($personel) {
                PersonelStatusHistory::firstOrCreate([
                    "start_date" => $personel->join_date,
                    "status" => 1,
                    "personel_id" => $personel->id,
                ], [
                    "end_date" => $personel->resign_date ?? null,
                    "change_by" => null,
                    "is_new" => false,
                    "is_checked" => false,
                ]);
            });
    }
}
