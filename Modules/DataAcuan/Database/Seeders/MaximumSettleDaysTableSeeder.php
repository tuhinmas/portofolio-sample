<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\MaximumSettleDay;

class MaximumSettleDaysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $max_settle_days = [
            [
                "personel_id" => "4f146e2f-227a-4d3d-8c08-0cb50df9496f",
                "max_settle_for" => "fee point marketing",
                "days" => 60,
                "year" => 2021
            ],
            [
                "personel_id" => "4f146e2f-227a-4d3d-8c08-0cb50df9496f",
                "max_settle_for" => "fee point marketing",
                "days" => 60,
                "year" => 2022
            ],
            [
                "personel_id" => "4f146e2f-227a-4d3d-8c08-0cb50df9496f",
                "max_settle_for" => "fee point marketing",
                "days" => 60,
                "year" => 2023
            ],
            [
                "personel_id" => "4f146e2f-227a-4d3d-8c08-0cb50df9496f",
                "max_settle_for" => "fee point marketing",
                "days" => 60,
                "year" => 2024
            ],
            [
                "personel_id" => "4f146e2f-227a-4d3d-8c08-0cb50df9496f",
                "max_settle_for" => "fee point marketing",
                "days" => 60,
                "year" => 2025
            ],
        ];

        foreach ($max_settle_days as $max_settle_day) {
            MaximumSettleDay::firstOrCreate($max_settle_day);
        }
    }
}
