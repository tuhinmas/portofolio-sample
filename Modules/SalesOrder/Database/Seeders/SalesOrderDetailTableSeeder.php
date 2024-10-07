<?php

namespace Modules\SalesOrder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class SalesOrderDetailTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $sales_order = SalesOrder::inRandomOrder()->first();
        $sales_order_details = [
            [
                'sales_order_id' => $sales_order->id,
                'quantity' => 5,
                'unit_price' => 10000,
                'total' => 50000,
                'sales_order_id' => $sales_order->id,
                'product_id' => Product::inRandomOrder()->first()->id
            ],
            [
                'sales_order_id' => $sales_order->id,
                'quantity' => 9,
                'unit_price' => 500000,
                'total' => 50000,
                'sales_order_id' => $sales_order->id,
                'product_id' => Product::inRandomOrder()->first()->id
            ],
            [
                'sales_order_id' => $sales_order->id,
                'quantity' => 1,
                'unit_price' => 5000,
                'total' => 500,
                'sales_order_id' => $sales_order->id,
                'product_id' => Product::inRandomOrder()->first()->id
            ],
        ];

        foreach($sales_order_details as $sales_order_detail){
            SalesOrderDetail::create($sales_order_detail);
        }
    }
}
