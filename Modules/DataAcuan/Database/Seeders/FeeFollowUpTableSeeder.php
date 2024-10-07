<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\FeeFollowUp;

class FeeFollowUpTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('fee_follow_ups')->delete();
        $fee = [
            [
                "id" => "59d6f1d3-3d23-4288-afbc-087c80840398",
                "follow_up_days" => 60,
                "fee" => 30,
                "settle_days" => 30
            ],
            [
                "id" => "01a91910-1398-4909-abab-b0f9807e5e45",
                "follow_up_days" => 90,
                "fee" => 20,
                "settle_days" => 60
            ],
        ];

        foreach ($fee as $v) {
            FeeFollowUp::create($v);
        }
    }
}
