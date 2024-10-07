<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class AdjusmentDistributorStockToSODTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * this
         * seeder only use in development, or to
         * adjust distributor stock first
         */
        Model::unguard();
        $adjusment_distributor_stocks = AdjustmentStock::all();
        if ($adjusment_distributor_stocks->count() > 0) {
            $adjusment_distributor_stocks->each(function ($adjusment) {
                $stock_sod = SalesOrderDetail::query()
                    ->whereHas("sales_order", function ($QQQ) use ($adjusment) {
                        return $QQQ
                            ->where(function ($QQQ) use ($adjusment) {
                                return $QQQ
                                    ->where("type", "1")
                                    ->where("store_id", $adjusment->dealer_id)
                                    ->whereIn("status", ["confirmed"])
                                    ->whereHas("invoiceHasOne", function ($QQQ) use ($adjusment) {
                                        return $QQQ->whereDate("created_at", "<=", $adjusment->opname_date);
                                    });
                            })
                            ->orWhere(function ($QQQ) use ($adjusment) {
                                return $QQQ
                                    ->where("type", "2")
                                    ->where("store_id", $adjusment->dealer_id)
                                    ->whereIn("status", ["confirmed"])
                                    ->where(function ($QQQ) use ($adjusment) {
                                        return $QQQ->where("date", "<=", $adjusment->opname_date);
                                    });
                            })
                            ->orWhere(function ($QQQ) use ($adjusment) {
                                return $QQQ
                                    ->where("type", "2")
                                    ->where("distributor_id", $adjusment->dealer_id)
                                    ->whereIn("status", ["returned"])
                                    ->where(function ($QQQ) use ($adjusment) {
                                        return $QQQ->whereDate("date", "<=", $adjusment->opname_date);
                                    });
                            });
                    })
                    ->leftJoin("sales_orders as s","s.id", "=", "sales_order_details.sales_order_id")
                    ->leftJoin("invoices as i","s.id", "=", "i.sales_order_id")
                    ->orderByRaw("if(s.type = 1, i.created_at, s.date) asc")
                    ->select("sales_order_details.*", "s.type", "s.date as confirmed_at", "i.created_at as confirmed_at")
                    ->groupBy("sales_order_details.id")
                    ->get();

                if ($adjusment->current_stock >= 0) {
                    $stock_sod->each(function ($stock) {
                        $stock->stock_out = $stock->stock;
                        $stock->save();
                    });
                } else {
                    // dump($adjusment->current_stock);
                    $adjusment->current_stock;

                    dump($adjusment->dealer_id);
                    dump($adjusment->product_id);
                    $stock_minus = $stock_sod->where("product_id", $adjusment->product_id);

                    // dd($stock_minus);
                    
                    if ((collect($stock_minus)->sum("stock") + $adjusment->current_stock) < 0) {
                        // dd("minus");
                    }

                    /* update stock out according stock minus */
                    collect($stock_minus)->each(function($stock)use($adjusment){
                        dump($stock->stock);
                        // dd($adjusment->current_stock);
                        // $stock->stock_out += $adjusment->current_stock;
                        // $stock->save();
                    });


                    // dd($stock_minus);
                }
            });
        }
    }
}
