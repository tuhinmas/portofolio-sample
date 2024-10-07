<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;

class SetDealerGradingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $grade = Grading::where("name", "!=", "Hitam")->get();
        $dealers = Dealer::all();
        foreach ($dealers as $dealer) {
            $dealer->grading_id = $grade->random()->id;
            $dealer->save();
        }
    }
}
