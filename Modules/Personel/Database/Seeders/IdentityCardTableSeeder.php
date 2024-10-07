<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\IdentityCard;

class IdentityCardTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $indentity_types = [
            ['name' => 'KTP'],
            ['name' => 'SIM A'],
            ['name' => 'SIM C'],
            ['name' => 'KTAS'],
            ['name' => 'PASSPORT'],
        ];

        foreach($indentity_types as $indentity){
            IdentityCard::create($indentity);
        }
    }
}
