<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerGrading;

class DealerGradingSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('dealer_gradings')->delete();
        $dealers = Dealer::all();
        foreach ($dealers as $dealer) {
            DealerGrading::create([
                "dealer_id" => $dealer->id,
                "grading_id" => $dealer->grading_id,
                "user_id" => auth()->id(),
            ]);
        }
    }
}
