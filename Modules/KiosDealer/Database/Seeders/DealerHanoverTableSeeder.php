<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealer\Entities\Handover;

class DealerHanoverTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $handover = StatusFee::where("name", "R")->first();
        $handover = $handover->id;
        Dealer::query()->update([
            'status_fee' => $handover,
        ]);
    }
}
