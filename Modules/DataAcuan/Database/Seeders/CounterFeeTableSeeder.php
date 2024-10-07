<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CounterFeeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('inactive_parameters')->delete();

        $parameters = [
            [
                "id" => "e0ca09a5-a4fb-4de7-87bb-9caba01d3b58",
                "name" => "inactive dealer",
                "parameter" => 90,
                "counter_fee" => 50
            ],
            [
                "id" => "4a3a69db-1b79-4a23-90cc-cc08df54a0d9",
                "name" => "inactive dealer",
                "parameter" => 60,
                "counter_fee" => 25
            ],
        ];

        foreach ($parameters as $param) {
            DB::table('inactive_parameters')->insert($param);
        }
    }
}
