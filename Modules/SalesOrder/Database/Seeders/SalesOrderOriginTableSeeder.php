<?php

namespace Modules\SalesOrder\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

class SalesOrderOriginTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $sales_order = SalesOrder::query()
            ->with([
                "sales_order_detail",
            ])
            ->where("type", "1")
            ->whereHas("dealer", function ($QQQ) {
                return $QQQ
                    ->where("is_distributor", "1")
                    ->where("id", "015dde8b-7e47-4d58-849e-c86f09b136b1");
            })
            ->whereHas("invoice")
            ->where("status", "confirmed")
            ->leftJoin("invoices as i", "i.sales_order_id", "=", "sales_orders.id")
            ->select("sales_orders.*")
            ->orderBy("i.created_at")
            ->first();

        /**
         * get indirect sale to this distributor
         */
        $sales_order_children = SalesOrder::query()
            ->with([
                "sales_order_detail",
            ])
            ->whereHas("sales_order_detail")
            ->where("type", "2")
            ->where("status", "confirmed")
            ->whereIn("distributor_id", $sales_order->pluck("store_id")->toArray())
            ->orderBy("date")
            ->get();

        $product_distributor = $sales_order->sales_order_detail;
        $origins = collect();

        collect($product_distributor)->each(function ($product) use ($sales_order_children, &$origins) {
            $current_stock = $product->stock;
            $total_product_out = 0;
            collect($sales_order_children)->each(function ($children) use ($product, &$origins, &$current_stock, &$total_product_out) {
                $product_sales_from_distributor = collect($children->sales_order_detail)->where("product_id", $product->product_id)->values();
                if ($product_sales_from_distributor->count() > 0) {

                    $last_stoct = $current_stock;
                    $current_stock -= $product_sales_from_distributor->toArray()[0]["quantity"];
                    if ($last_stoct <= 0) {
                        $last_stoct = 0;
                    }
                    
                    $total_product_out += $current_stock <= 0 ? $last_stoct : $product_sales_from_distributor->toArray()[0]["quantity"];

                    $origins->push([
                        "current_order" => $children->id,
                        "direct_id" => $product->sales_order_id,
                        "parent_id" => $product->sales_order_id,
                        "sales_order_detail_id" => $product->id,
                        "product_id" => $product->product_id,
                        "quantity_from_origin" => 0,
                        "direct_price" => $product->unit_price,
                        "stock" => $product->stock,
                        "product_order" => $product_sales_from_distributor->toArray()[0]["quantity"],
                        "product_out" => $current_stock <= 0 ? $last_stoct : $product_sales_from_distributor->toArray()[0]["quantity"],
                        "current_stock" => $current_stock <= 0 ? 0 : $current_stock,
                        "total_product_out" => $total_product_out,
                    ]);
                }
            });
        });

        foreach ($sales_order_children as $order) {
            foreach ($order->sales_order_detail as $order_detail) {
                $direct_order = SalesOrder::query()
                    ->with([
                        "sales_order_detail" => function ($QQQ) use ($order_detail) {
                            return $QQQ
                                ->where("product_id", $order_detail->product_id)
                                ->whereNotNull("unit_price");
                        },
                    ])
                    ->whereHas("sales_order_detail", function ($QQQ) use ($order_detail) {
                        return $QQQ
                            ->where("product_id", $order_detail->product_id)
                            ->whereNotNull("unit_price");
                    })
                    ->where("type", "1")
                    ->where("status", "confirmed")
                    ->where("store_id", $order->distributor_id)
                    ->first();

                $second_direct_order = SalesOrder::query()
                    ->with([
                        "sales_order_detail" => function ($QQQ) use ($order_detail) {
                            return $QQQ
                                ->where("product_id", $order_detail->product_id)
                                ->whereNotNull("unit_price");
                        },
                    ])
                    ->whereHas("sales_order_detail", function ($QQQ) use ($order_detail) {
                        return $QQQ
                            ->where("product_id", $order_detail->product_id)
                            ->whereNotNull("unit_price");
                    })
                    ->where("store_id", $order->distributor_id)
                    ->when($direct_order, function ($QQQ) use ($direct_order) {
                        return $QQQ->where("id", "!=", $direct_order->id);
                    })
                    ->where("type", "1")
                    ->where("status", "confirmed")
                    ->first();

                $splited = false;
                $iteration = 1;
                $order_id = [];
                if ($direct_order) {
                    $order_id[] = $direct_order->id;
                    if ($direct_order->sales_order_detail) {
                        $quantity_from_origin[] = $order_detail->quantity;
                        if ($second_direct_order) {
                            $order_id[] = $second_direct_order->id;
                            if ($second_direct_order->sales_order_detail && $order_detail->quantity > 1) {
                                $iteration = 2;
                                $splited = true;
                                $quantity_from_origin[0] = ceil($order_detail->quantity / 2);
                                $quantity_from_origin[1] = floor($order_detail->quantity / 2);
                            }
                        }
                        for ($i = 0; $i < $iteration; $i++) {
                            SalesOrderOrigin::firstOrCreate([
                                "direct_id" => $order_id[$i],
                                "parent_id" => $order->id,
                                "sales_order_detail_id" => $order_detail->id,
                                "product_id" => $order_detail->product_id,
                                "quantity_from_origin" => $quantity_from_origin[$i],
                                "direct_price" => collect($direct_order->sales_order_detail)->where("product_id", $order_detail->product_id)->first()->unit_price,
                                "is_returned" => 0,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
