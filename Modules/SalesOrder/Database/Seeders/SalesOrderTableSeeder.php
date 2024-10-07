<?php

namespace Modules\SalesOrder\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\SalesOrder\Database\Seeders\SalesOrderDatabaseSeeder;

class SalesOrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $store = Store::inRandomOrder()->first();
        $personel = Personel::inRandomOrder()->first();
        $payment_method = PaymentMethod::inRandomOrder()->first();

        $sales_orders = [
            [
                'store_id' => $store->id,
                'personel_id' => $personel->id,
                'payment_method_id' => $payment_method->id,
                'recipient_phone_number' => '085956289255',
                'delivery_location' => 'https://www.google.com/maps/place/Ngatiran+Mebel/@-7.728374,110.4160848,19.5z/data=!4m5!3m4!1s0x2e7a59d4dfd731ef:0x782a722b0016a1b!8m2!3d-7.7284198!4d110.4161217',
                'sub_total' => 100000,
                'discount' => 50000,
                'total' => 50000,
                'type' => 1,
                'model' => 1
            ],
        ];
        foreach($sales_orders as $sales_order){
            SalesOrder::create($sales_order);
        }
    }
}
