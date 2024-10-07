<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Blood;
use Illuminate\Database\Eloquent\Model;

class BloodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $bloods = [
            [
                "name" => "a"
            ],
            [
                "name" => "b"
            ],
            [
                "name" => "ab"
            ],
            [
                "name" => "o"
            ],
        ];
        foreach ($bloods as $blood) {
            Blood::firstOrCreate($blood);
        }
    }
}
