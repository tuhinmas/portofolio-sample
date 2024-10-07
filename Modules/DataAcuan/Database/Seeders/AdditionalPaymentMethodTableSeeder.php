<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\PaymentMethod;

class AdditionalPaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('payment_methods')->delete();
        $credit = [
            [
                "name" => "Cash",
                "days" => 0
            ],
            [
                "name" => "Credit",
                "days" => 7
            ],
            [
                "name" => "Credit",
                "days" => 14
            ],
            [
                "name" => "Credit",
                "days" => 30
            ],
            [
                "name" => "Credit",
                "days" => 45
            ],
            [
                "name" => "Credit",
                "days" => 60
            ],
            [
                "name" => "Bilyet Giro",
                "days" => 7
            ],
            [
                "name" => "Bilyet Giro",
                "days" => 14
            ],
            [
                "name" => "Bilyet Giro",
                "days" => 30
            ],
            [
                "name" => "Bilyet Giro",
                "days" => 45
            ],
            [
                "name" => "Bilyet Giro",
                "days" => 60
            ]
        ];

        foreach ($credit as $method) {
            PaymentMethod::firstOrCreate($method);
        }
    }
}
