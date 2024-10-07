<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;
use Modules\DataAcuan\Entities\PaymentMethod;

class DealerPaymentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('dealer_payment_methods')->delete();
        $dealers = Dealer::get();
        $merah = Grading::where("name", "Merah")->pluck("id")->first();
        $payment = PaymentMethod::all();
        foreach ($dealers as $dealer) {
            if ($dealer->grading_id == $merah){
                $dealerPayment = $payment->where("name", "Cash")->pluck("id");
                $dealer->dealerPayment()->attach($dealerPayment->all());
            }
        }
    }
}
