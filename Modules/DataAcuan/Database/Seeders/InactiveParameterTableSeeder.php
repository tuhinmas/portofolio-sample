<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\InactiveParameter;

class InactiveParameterTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        InactiveParameter::create([
            "name" => "inactive dealer",
            'parameter' => 90
        ]);
    }
}
