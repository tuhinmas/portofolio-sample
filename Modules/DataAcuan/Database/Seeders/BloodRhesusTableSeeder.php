<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\BloodRhesus;

class BloodRhesusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $rhesus = [
            [
                "name" => "negatif",
            ],
            [
                "name" => "positif",
            ],
        ];
        foreach ($rhesus as $rhes) {
            BloodRhesus::firstOrCreate($rhes);
        }
    }
}
