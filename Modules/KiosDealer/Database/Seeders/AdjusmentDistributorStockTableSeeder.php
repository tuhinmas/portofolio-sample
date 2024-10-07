<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class AdjusmentDistributorStockTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $distributors = Dealer::query()
            ->with([
                "contractDistributor" => function ($QQQ) {
                    return $QQQ->orderBy("contract_start");
                },
            ])
            ->whereHas("contractDistributor", function ($QQQ) {
                return $QQQ->orderBy("contract_start");
            })
            ->get();
        
        $all_stock_distributor = collect();
        $distributors->each(function ($distributor) use(&$all_stock_distributor){
            $distributor_purchase = SalesOrderDetail::query()
                ->whereHas("sales_order", function ($QQQ) use ($distributor) {
                    return $QQQ
                        ->where(function ($QQQ) use ($distributor) {
                            return $QQQ
                                ->where("type", "1")
                                ->where("store_id", $distributor->contractDistributor[0]->dealer_id)
                                ->whereIn("status", ["confirmed"])
                                ->whereHas("invoiceHasOne", function ($QQQ) use ($distributor) {
                                    return $QQQ
                                        ->where("created_at", ">=", $distributor->contractDistributor[0]->contract_start)
                                        ->where("created_at", "<=", $distributor->contractDistributor[0]->contract_end);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($distributor) {
                            return $QQQ
                                ->where("type", "2")
                                ->where("store_id", $distributor->contractDistributor[0]->dealer_id)
                                ->whereIn("status", ["confirmed"])
                                ->where(function ($QQQ) use ($distributor) {
                                    return $QQQ
                                        ->where("date", ">=", $distributor->contractDistributor[0]->contract_start)
                                        ->where("date", "<=", $distributor->contractDistributor[0]->contract_end);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($distributor) {
                            return $QQQ
                                ->where("type", "2")
                                ->where("distributor_id", $distributor->contractDistributor[0]->dealer_id)
                                ->whereIn("status", ["returned"])
                                ->where(function ($QQQ) use ($distributor) {
                                    return $QQQ
                                        ->where("date", ">=", $distributor->contractDistributor[0]->contract_start)
                                        ->where("date", "<=", $distributor->contractDistributor[0]->contract_end);
                                });
                        });
                })
                ->get();

            $distributor_purchase_grouped = $distributor_purchase->groupBy("product_id");

            $product_purchases = collect();

            /* get distributor purchases */
            collect($distributor_purchase_grouped)->each(function ($purchases, $product_id) use (&$product_purchases) {
                collect($purchases)->sum("quantity");
                $product_purchases->push([
                    "product_id" => $product_id,
                    "product_purchase" => collect($purchases)->sum("quantity"),
                    "product_sale" => 0,
                ]);
            });

            /* get distributor sales */
            $distributor_sales = SalesOrderDetail::query()
                ->whereHas("sales_order", function ($QQQ) use ($distributor) {
                    return $QQQ
                        ->where("type", "2")
                        ->where("distributor_id", $distributor->contractDistributor[0]->dealer_id)
                        ->whereIn("status", ["confirmed", "pending"]);
                })
                ->get();

            $product_purchases = collect($product_purchases)->map(function ($product_purchase) use ($distributor_sales, $distributor) {
                $product_sale = $distributor_sales->where("product_id", $product_purchase["product_id"])->sum("quantity");
                $product_purchase["product_sale"] = $product_sale;
                $product_purchase["opname_date"] = $distributor->contractDistributor[0]->contract_end;
                $product_purchase["real_stock"] = 0;
                $product_purchase["current_stock"] = ($product_purchase["product_purchase"] - $product_sale);
                $product_purchase["dealer_id"] = $distributor->contractDistributor[0]->dealer_id;
                return $product_purchase;
            });

            $product_purchases->each(function($purchase)use($distributor){
                AdjustmentStock::firstOrCreate([
                    "opname_date" => $distributor->contractDistributor[0]->contract_end,
                    "product_id" => $purchase["product_id"],
                    "dealer_id" => $distributor->contractDistributor[0]->dealer_id
                ],[
                    "real_stock" => $purchase["real_stock"],
                    "current_stock" => $purchase["current_stock"],
                ]);
            });
            

            $all_stock_distributor->push($product_purchases->toArray());

            dd($all_stock_distributor);
        });
    }
}
