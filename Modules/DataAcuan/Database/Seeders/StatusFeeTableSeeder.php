<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\StatusFee;

class StatusFeeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('status_fee')->delete();
        $status_fee = [
            [
                "name" => "L1",
                "percentage" => 30,
            ],
            [
                "name" => "L2",
                "percentage" => 60,
            ],
            [
                "name" => "L3",
                "percentage" => 100,
            ],
            [
                "name" => "R",
                "percentage" => 100,
            ],
        ];

        foreach ($status_fee as $value) {
            StatusFee::firstOrCreate($value);
        }
    }
}
