<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\PaymentMethod;

class PaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $payment_methods = [
            [
                'name' => 'Cash',
                "days" => 0
            ],
            [
                'name' => 'Kredit',
                "days" => 10
            ],
            [
                'name' => 'Bilyet Giro',
                "days" => 10
            ],
        ];

        PaymentMethod::query()->delete();
        foreach($payment_methods as $payment_method){
            PaymentMethod::create($payment_method);
        }
    }
}
