<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Handover;

class HandoverTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $handover = [
            [
                "name" => "Reguler",
                "fee" => "100"
            ],
            [
                "name" => "L1",
                "fee" => "30"
            ],
            [
                "name" => "L2",
                "fee" => "60"
            ],
            [
                "name" => "L3",
                "fee" => "100"
            ],
            [
                "name" => "Return",
                "fee" => "0"
            ],
        ];

        foreach($handover as $over){
            Handover::create($over);
        }
    }
}
