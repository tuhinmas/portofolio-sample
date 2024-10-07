<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Religion;

class ReligionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $religions = [
            ['name' => 'Islam'],
            ['name' => 'Kristen'],
            ['name' => 'Protestan'],
            ['name' => 'Hindu'],
            ['name' => 'Buddha'],
            ['name' => 'Konghucu'],
        ];

        foreach($religions as $religion){
            Religion::create($religion);
        }
    }
}
