<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\AgencyLevel;

class AgencyLevelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $agency_level = [
            [
                'name' => 'D1',
                "agency" => 5
            ],
            [
                'name' => 'D2',
                "agency" => 4
            ],
            [
                'name' => 'R1',
                "agency" => 3
            ],
            [
                'name' => 'R2',
                "agency" => 2
            ],
            [
                'name' => 'R3',
                "agency" => 1
            ],
        ];

        foreach($agency_level as $agency){
            AgencyLevel::create($agency);
        }
    }
}
