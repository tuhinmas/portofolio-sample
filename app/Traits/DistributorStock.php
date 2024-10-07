<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;
use Modules\Distributor\Entities\DistributorContract;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\KiosDealerV2\Entities\DistributorProductSuspended;
use Modules\KiosDealerV2\Entities\DistributorSuspended;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

/**
 * distributor stock handler
 */
trait DistributorStock
{
    public function distributorProductCurrentStockAdjusmentBased($distributor_id, $product_id, $date = null, $active_contract = null)
    {
        Dealer::findOrFail($distributor_id);

        if (!$active_contract) {
            $active_contract = $this->distributorActiveContract($distributor_id, $date);
        }
        $original_date = $date;
        if ($active_contract) {

            /* fisrt stock contract */
            $first_stock = DB::table('adjustment_stock')
                ->whereNull("deleted_at")
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->whereDate("opname_date", ">=", $active_contract->contract_start)
                ->whereDate("opname_date", "<=", $active_contract->contract_end)
                ->where("is_first_stock", "1")
                ->orderBy("opname_date")
                ->first();

            /* last adjustment inside contract */
            $last_adjustment = DB::table('adjustment_stock')
                ->whereNull("deleted_at")
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->whereDate("opname_date", ">=", $active_contract->contract_start)
                ->whereDate("opname_date", "<=", ($date ?? $active_contract->contract_end))
                ->orderBy("opname_date", "desc")
                ->orderBy("created_at", "desc")
                ->first();

            /**
             * stock check is not according date only
             * it is need to check sales after date
             * until next opname or last sales
             */
            if ($date) {
                $adjusment_after_date = DB::table('adjustment_stock')
                    ->whereNull("deleted_at")
                    ->where("dealer_id", $distributor_id)
                    ->where("product_id", $product_id)
                    ->whereDate("opname_date", ">", $date)
                    ->whereDate("opname_date", "<=", $active_contract->contract_end)
                    ->orderBy("opname_date", "asc")
                    ->orderBy("created_at", "asc")
                    ->first();

                if ($adjusment_after_date) {
                    $date = Carbon::parse($adjusment_after_date->opname_date)->subDay();
                } else {
                    $date = $active_contract->contract_end;
                }
            }

            $distributor_pickup = SalesOrderDetail::query()
                ->with([
                    "product",
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice" => function ($QQQ) {
                                return $QQQ->with([
                                    "creditMemos.creditMemoDetail",
                                ]);
                            },
                        ]);
                    },
                ])
                ->where("product_id", $product_id)
                ->whereHas("sales_order", function ($QQQ) use ($last_adjustment, $distributor_id, $active_contract, $date) {
                    return $QQQ->distributorPickUpDuringContract($distributor_id, ($last_adjustment ? $last_adjustment->opname_date : $active_contract->contract_start), ($date ? $date : $active_contract->contract_end));
                })
                ->get();

            $distributor_sales = SalesOrderDetail::query()
                ->with([
                    "salesOrder",
                ])
                ->where("product_id", $product_id)
                ->whereHas("sales_order", function ($QQQ) use ($distributor_id, $active_contract, $last_adjustment, $date) {
                    return $QQQ->distributorSalesDuringContract($distributor_id, ($last_adjustment ? $last_adjustment->opname_date : $active_contract->contract_start), ($date ? $date : $active_contract->contract_end));
                })
                ->get();

            $pickup_product = 0;
            $pickup_return = 0;
            $first_stock_origin = 0;

            if ($distributor_pickup->count() > 0) {
                $first_stock_origin = $first_stock ? $first_stock->real_stock : null;
                $pickup_product = $distributor_pickup

                /* no need to filter, filter in scope */
                // ->filter(function ($sales) use ($last_adjustment, $active_contract) {
                //     return confirmation_time($sales->sales_order)->format("Y-m-d") >= ($last_adjustment ? $last_adjustment->opname_date : $active_contract->contract_start);
                // })
                    ->sum("quantity");

                $pickup_return = $distributor_pickup->sum("returned_quantity");
            }

            $sales_product = 0;
            $sales_return = 0;
            if ($distributor_sales->count() > 0) {
                $sales_product = $distributor_sales
                    ->filter(function ($sales) use ($last_adjustment, $active_contract) {
                        return confirmation_time($sales->salesOrder)->format("Y-m-d") >= ($last_adjustment ? $last_adjustment->opname_date : $active_contract->contract_start);
                    })
                    ->sum("quantity");

                $sales_return = $distributor_sales->sum("returned_quantity");
            }

            $current_stock = ($last_adjustment ? $last_adjustment->real_stock : 0) + $pickup_product + $sales_return - $sales_product - $pickup_return;

            /**
             * -------------------------------
             * last year stock
             * --------------------------
             */
            $adjustment_stock_last_year = DB::table('adjustment_stock')
                ->whereNull("deleted_at")
                ->where("contract_id", $active_contract->id)
                ->where("product_id", $product_id)
                ->whereDate("opname_date", "<=", ($original_date ? Carbon::parse($original_date)->subYear()->endOfYear()->format("Y-m-d") : now()->subYear()->endOfYear()->format("Y-m-d")))
                ->orderBy("opname_date", "desc")
                ->orderBy("created_at", "desc")
                ->first();

            $pickup_product_until_last_year = $distributor_pickup
                ->filter(function ($sales) use ($original_date) {
                    return confirmation_time($sales->salesOrder)->format("Y-m-d") <= ($original_date ? Carbon::parse($original_date)->subYear()->endOfYear()->format("Y-m-d") : now()->subYear()->endOfYear()->format("Y-m-d"));
                })
                ->filter(function ($sales) use ($adjustment_stock_last_year, $active_contract) {
                    return confirmation_time($sales->salesOrder)->format("Y-m-d") <= ($adjustment_stock_last_year ? $adjustment_stock_last_year->opname_date : $active_contract->contract_start);
                })
                ->sum(function ($order_detail) {
                    return $order_detail->quantity - $order_detail->returned_quantity;
                });

            $sales_product_until_last_year = $distributor_sales
                ->filter(function ($sales) use ($original_date) {
                    return confirmation_time($sales->salesOrder)->format("Y-m-d") <= ($original_date ? Carbon::parse($original_date)->subYear()->endOfYear()->format("Y-m-d") : now()->subYear()->endOfYear()->format("Y-m-d"));
                })
                ->filter(function ($sales) use ($adjustment_stock_last_year, $active_contract) {
                    return confirmation_time($sales->salesOrder)->format("Y-m-d") <= ($adjustment_stock_last_year ? $adjustment_stock_last_year->opname_date : $active_contract->contract_start);
                })
                ->sum(function ($order_detail) {
                    return $order_detail->quantity - $order_detail->returned_quantity;
                });

            $last_year_stock = ($adjustment_stock_last_year ? $adjustment_stock_last_year->real_stock : 0) + $pickup_product_until_last_year - $sales_product_until_last_year;

            $distributor_product_stock = (object) [
                "pickup_product" => $pickup_product,
                "pickup_return" => $pickup_return,
                "sales_return" => $sales_return,
                "sales_product" => $sales_product,
                "adjustment_stock" => (int) ($last_adjustment ? $last_adjustment->real_stock : 0),
                "current_stock" => $current_stock,
                "last_adjustment" => $last_adjustment,
                "first_stock" => $first_stock,
                "last_year_stock" => $last_year_stock,
                "is_distributor" => true,
            ];

            return $distributor_product_stock;
        }

        return (object) [
            "product_pickup" => 0,
            "product_returned" => 0,
            "product_sales" => 0,
            "product_sales_return" => 0,
            "product_sales_qty_in_return" => 0,
            "product_sales_in_return_fix" => 0,
            "adjustment_stock" => 0,
            "current_stock" => 0,
            "first_stock" => 0,
            "last_adjustment" => 0,
            "credit_memo" => 0,
            "last_year_stock" => 0,
            "is_distributor" => false,
        ];
    }

    /**
     * distributor previous
     */
    public function distributorProductStockPreviousBeforeAdjusment($distributor_id, $product_id, $current_adjusment)
    {

        Dealer::findOrFail($distributor_id);
        Product::findOrFail($product_id);

        $active_contract = $this->distributorActiveContract($distributor_id);

        /**
         * if there actice contract
         */
        if ($active_contract) {

            /* last adjustment inside contract */
            $previous_adjustment = AdjustmentStock::query()
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->whereNull("deleted_at")
                ->whereDate("opname_date", ">=", $active_contract->contract_start)
                ->whereDate("opname_date", "<=", $active_contract->contract_end)
                ->orderBy("opname_date", "desc")
                ->orderBy("created_at", "desc")
                ->where("opname_date", "<=", $current_adjusment->opname_date)
                ->where("id", "!=", $current_adjusment->id)
                ->first();

            if ($previous_adjustment) {

                $distributor_pickup = SalesOrderDetail::query()
                    ->with([
                        "sales_order",
                        "product",
                    ])
                    ->where("product_id", $product_id)
                    ->whereHas("sales_order", function ($QQQ) use ($previous_adjustment, $distributor_id, $active_contract, $current_adjusment) {
                        return $QQQ->distributorPickUpDuringContract($distributor_id, ($previous_adjustment ? $previous_adjustment->opname_date : $active_contract->contract_start), $current_adjusment->opname_date);
                    })
                    ->get();

                /**
                 * distributor sales before opname
                 */
                $distributor_sales = SalesOrderDetail::query()
                    ->where("product_id", $product_id)
                    ->whereHas("sales_order", function ($QQQ) use ($distributor_id, $active_contract, $previous_adjustment, $current_adjusment) {
                        return $QQQ->distributorSalesDuringContract($distributor_id, ($previous_adjustment ? $previous_adjustment->opname_date : $active_contract->contract_start), $current_adjusment->opname_date);
                    })
                    ->get();

                $product_stock_include_return = 0;
                $product_stock = 0;
                $product_return = 0;
                $product_stock_after_return = 0;
                $product_quantity_in_return = 0;
                $first_stock_origin = 0;
                $stock_opname_origin = 0;

                if ($distributor_pickup->count() > 0) {

                    $product_stock = $distributor_pickup->whereIn("sales_order.status", ["confirmed", "pending"])->sum("quantity");
                    $first_stock_origin = $previous_adjustment ? $previous_adjustment->real_stock : null;
                    $product_return = $distributor_pickup->whereIn("sales_order.status", ["returned"])->sum("returned_quantity");
                    $product_quantity_in_return = $distributor_pickup->whereIn("sales_order.status", ["returned"])->sum("quantity");
                    $product_stock_after_return = $product_quantity_in_return - $product_return;
                    $product_stock_include_return = $product_stock + $product_stock_after_return;
                }

                $product_sales = 0;
                $product_sales_return = 0;
                $product_sales_in_return_fix = 0;
                $product_sales_qty_in_return = 0;
                if ($distributor_sales->count() > 0) {
                    $product_sales = $distributor_sales->whereNull("returned_quantity")->sum("quantity");
                    $product_sales_return = $distributor_sales->whereNotNull("returned_quantity")->sum("returned_quantity");
                    $product_sales_qty_in_return = $distributor_sales->whereNotNull("returned_quantity")->sum("quantity");
                    $product_sales_in_return_fix = $product_sales_qty_in_return - $product_sales_return;
                }

                $previous_stock = ($product_stock_include_return + ($previous_adjustment ? $previous_adjustment->real_stock : 0)) - ($product_sales + $product_sales_in_return_fix);

                /**
                 * if distributor does not have order yet, then stock
                 * is fromadjustment
                 */
                if ($distributor_pickup->count() == 0) {
                    $previous_stock = ($previous_adjustment ? $previous_adjustment->real_stock : 0) - ($product_sales + $product_sales_in_return_fix);
                }

                $distributor_product_stock = (object) [
                    "product_pickup" => $product_stock + $product_quantity_in_return,
                    "product_returned" => $product_return,
                    "previous_self_sales" => $previous_adjustment->self_sales,
                    "product_sales" => $product_sales,
                    "product_sales_return" => $product_sales_return,
                    "product_sales_in_return_fix" => $product_sales_in_return_fix,
                    "adjustment_stock" => (int) ($previous_adjustment ? $previous_adjustment->real_stock : 0),
                    "previous_stock" => $previous_stock,
                    "previous_adjustment" => $previous_adjustment,
                ];

                return $distributor_product_stock;
            }

            return (object) [
                "product_pickup" => 0,
                "product_returned" => 0,
                "previous_self_sales" => 0,
                "product_sales" => 0,
                "product_sales_return" => 0,
                "product_sales_in_return_fix" => 0,
                "adjustment_stock" => 0,
                "previous_stock" => 0,
                "previous_adjustment" => 0,
            ];

            return $previous_adjustment;
        }
    }

    /**
     * distributor system stock according contract
     *
     * @param [type] $distributor_id
     * @param [type] $product_id
     * @param [type] $max_date
     * @return void
     */
    public function stockSystem($distributor_id, $product_id, $max_date = null)
    {
        Dealer::findOrFail($distributor_id);
        Product::withTrashed()->findOrFail($product_id);

        $active_contract = $this->distributorActiveContract($distributor_id, $max_date);
        if ($active_contract) {

            /* fisrt stock contract */
            $first_stock = DB::table('adjustment_stock')
                ->whereNull("deleted_at")
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->whereDate("opname_date", ">=", $active_contract->contract_start)
                ->whereDate("opname_date", "<=", $active_contract->contract_end)
                ->where("is_first_stock", "1")
                ->orderBy("opname_date")
                ->first();

            $last_adjustment = null;
            if ($first_stock) {

                /* last adjustment inside contract */
                $last_adjustment = DB::table('adjustment_stock')
                    ->whereNull("deleted_at")
                    ->where("dealer_id", $distributor_id)
                    ->where("product_id", $product_id)
                    ->whereDate("opname_date", ">=", $active_contract->contract_start)
                    ->whereDate("opname_date", "<=", ($max_date ?? $active_contract->contract_end))
                    ->orderBy("opname_date", "desc")
                    ->orderBy("created_at", "desc")
                    ->first();
            }

            /**
             * stock check is not according date only
             * it is need to check sales after date
             * until next opname or last sales
             */
            if ($max_date) {
                $adjusment_after_date = DB::table('adjustment_stock')
                    ->whereNull("deleted_at")
                    ->where("dealer_id", $distributor_id)
                    ->where("product_id", $product_id)
                    ->whereDate("opname_date", ">", $max_date)
                    ->whereDate("opname_date", "<=", $active_contract->contract_end)
                    ->orderBy("opname_date", "asc")
                    ->orderBy("created_at", "asc")
                    ->first();

                if ($adjusment_after_date) {
                    $max_date = Carbon::parse($adjusment_after_date->opname_date)->subDay();
                } else {
                    $max_date = $active_contract->contract_end;
                }
            }

            $distributor_pickup = SalesOrderDetail::query()
                ->with([
                    "product",
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice" => function ($QQQ) {
                                return $QQQ->with([
                                    "creditMemos.creditMemoDetail",
                                ]);
                            },
                        ]);
                    },
                ])
                ->where("product_id", $product_id)
                ->whereHas("sales_order", function ($QQQ) use ($last_adjustment, $max_date, $distributor_id, $active_contract) {
                    return $QQQ->distributorPickUpDuringContract($distributor_id, $active_contract->contract_start, ($max_date ? $max_date : $active_contract->contract_end));
                })
                ->get();

            $distributor_sales = SalesOrderDetail::query()
                ->where("product_id", $product_id)
                ->whereHas("sales_order", function ($QQQ) use ($distributor_id, $active_contract, $max_date) {
                    return $QQQ->distributorSalesDuringContract($distributor_id, $active_contract->contract_start, ($max_date ? $max_date : $active_contract->contract_end));
                })
                ->get();

            $pickup_product = 0;
            $pickup_return = 0;
            $first_stock_origin = 0;
            if ($distributor_pickup->count() > 0) {

                $first_stock_origin = $first_stock ? $first_stock->real_stock : null;
                $pickup_product = $distributor_pickup

                /* no need to filter, filter in scope */
                // ->filter(function ($sales) use ($last_adjustment, $active_contract) {
                //     return confirmation_time($sales->salesOrder)->format("Y-m-d") >= $active_contract->contract_start;
                // })
                    ->sum("quantity");

                $pickup_return = $distributor_pickup->sum("returned_quantity");
            }

            $sales_product = 0;
            $sales_return = 0;
            if ($distributor_sales->count() > 0) {
                $sales_product = $distributor_sales->sum("quantity");
                $sales_return = $distributor_sales->sum("returned_quantity");
            }

            $stock_system = ($pickup_product + $sales_return) - ($pickup_return + $sales_product) + ($first_stock ? $first_stock->real_stock : 0);

            $distributor_product_stock = (object) [
                "pickup_product" => $pickup_product,
                "pickup_return" => $pickup_return,
                "sales_product" => $sales_product,
                "sales_return" => $sales_return,
                "adjustment_stock" => (int) ($last_adjustment ? $last_adjustment->real_stock : 0),
                "current_stock_system" => $stock_system,
                "first_stock" => $first_stock ? (int) $first_stock->real_stock : 0,
                "last_adjustment" => $last_adjustment,
            ];

            return $distributor_product_stock;
        }
        return (object) [
            "pickup_product" => 0,
            "pickup_return" => 0,
            "sales_product" => 0,
            "sales_return" => 0,
            "adjustment_stock" => 0,
            "current_stock_system" => 0,
            "first_stock" => 0,
            "last_adjustment" => 0,
            "credit_memo" => 0,
        ];
    }

    public function distributorProductStockPreviousContract($distributor_id, $product_id)
    {
        Dealer::findOrFail($distributor_id);

        $previous_contract = $this->distributorPrevoiusContract($distributor_id);

        if ($previous_contract) {

            /* fisrt stock contract */
            $first_stock = AdjustmentStock::query()
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->where("is_first_stock", "1")
                ->where("opname_date", ">=", $previous_contract->contract_start)
                ->where("opname_date", "<=", $previous_contract->contract_end)
                ->orderBy("opname_date")
                ->first();

            /* last adjustment inside contract */
            $last_adjustment = AdjustmentStock::query()
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->where("opname_date", ">=", $previous_contract->contract_start)
                ->where("opname_date", "<=", $previous_contract->contract_end)
                ->orderBy("opname_date", "desc")
                ->orderBy("created_at", "desc")
                ->first();

            $distributor_pickup = SalesOrderDetail::query()
                ->with([
                    "sales_order",
                    "product",
                ])
                ->where("product_id", $product_id)
                ->whereHas("sales_order", function ($QQQ) use ($last_adjustment, $distributor_id, $previous_contract) {
                    return $QQQ->distributorPickUpDuringContract($distributor_id, ($last_adjustment ? $last_adjustment->opname_date : $previous_contract->contract_start), $previous_contract->contract_end);
                })
                ->get();

            $distributor_sales = SalesOrderDetail::query()
                ->where("product_id", $product_id)
                ->whereHas("sales_order", function ($QQQ) use ($distributor_id, $previous_contract, $last_adjustment) {
                    return $QQQ->distributorSalesDuringContract($distributor_id, ($last_adjustment ? $last_adjustment->opname_date : $previous_contract->contract_start), $previous_contract->contract_end);
                })
                ->get();

            $product_stock_include_return = 0;
            $product_stock = 0;
            $product_return = 0;
            $product_stock_after_return = 0;
            $product_quantity_in_return = 0;
            $first_stock_origin = 0;
            $stock_opname_origin = 0;

            if ($distributor_pickup->count() > 0) {

                $product_stock = $distributor_pickup->whereIn("sales_order.status", ["confirmed", "pending"])->sum("quantity");
                $first_stock_origin = $first_stock ? $first_stock->real_stock : null;
                $product_return = $distributor_pickup->whereIn("sales_order.status", ["returned"])->sum("returned_quantity");
                $product_quantity_in_return = $distributor_pickup->whereIn("sales_order.status", ["returned"])->sum("quantity");
                $product_stock_after_return = $product_quantity_in_return - $product_return;
                $product_stock_include_return = $product_stock + $product_stock_after_return;
            }

            $product_sales = 0;
            $product_sales_return = 0;
            $product_sales_in_return_fix = 0;
            $product_sales_qty_in_return = 0;
            if ($distributor_sales->count() > 0) {
                $product_sales = $distributor_sales->whereNull("returned_quantity")->sum("quantity");
                $product_sales_return = $distributor_sales->whereNotNull("returned_quantity")->sum("returned_quantity");
                $product_sales_qty_in_return = $distributor_sales->whereNotNull("returned_quantity")->sum("quantity");
                $product_sales_in_return_fix = $product_sales_qty_in_return - $product_sales_return;
            }

            $current_stock = ($product_stock_include_return + ($last_adjustment ? $last_adjustment->real_stock : 0)) - ($product_sales + $product_sales_in_return_fix);

            /**
             * if distributor does not have order yet, then stock
             * is fromadjustment
             */
            if ($distributor_pickup->count() == 0) {
                $current_stock = ($last_adjustment ? $last_adjustment->real_stock : 0) - ($product_sales + $product_sales_in_return_fix);
            }

            $distributor_product_stock = (object) [
                "product_pickup" => $product_stock,
                "product_returned" => $product_return,
                "product_sales_include_return" => $product_sales + $product_sales_qty_in_return,
                "product_sales_returned" => $product_sales_return,
                "product_sales_qty_in_return" => $product_sales_qty_in_return,
                "product_sales_in_return_fix" => $product_sales_in_return_fix,
                "adjustment_stock" => (int) ($last_adjustment ? $last_adjustment->real_stock : 0),
                "current_stock" => $current_stock,
                "last_adjustment" => $last_adjustment,
                "first_stock" => $first_stock,
            ];

            return $distributor_product_stock;
        }

        return (object) [
            "product_pickup" => 0,
            "product_returned" => 0,
            "product_sales" => 0,
            "product_sales_return" => 0,
            "product_sales_qty_in_return" => 0,
            "product_sales_in_return_fix" => 0,
            "adjustment_stock" => 0,
            "current_stock" => 0,
            "first_stock" => 0,
            "last_adjustment" => 0,
        ];
    }

    /**
     * suspended product distributor
     *
     * @param [type] $distributor_id
     * @param [type] $product_id
     * @return void
     */
    public function suspendDistributorProduct($distributor_id, $product_id)
    {

        $distributor_suspended = DistributorSuspended::firstOrCreate([
            "dealer_id" => $distributor_id,
        ], [
            "suspended_date" => now()->format("Y-m-d"),
        ]);

        $distributor_product = DistributorProductSuspended::firstOrCreate([
            "distributor_suspended_id" => $distributor_suspended->id,
            "product_id" => $product_id,
        ], [
            "is_suspended" => 1,
        ]);

        return $distributor_product;
    }

    /**
     * revoke suspend
     *
     * @param [type] $distributor_id
     * @param [type] $product_id
     * @return void
     */
    public function revokeSuspend($distributor_id, $product_id)
    {
        $distributor_suspended = DistributorSuspended::query()
            ->where("dealer_id", $distributor_id)
            ->first();

        if ($distributor_suspended) {
            $distributor_product = DistributorProductSuspended::query()
                ->whereHas("distributorSuspended", function ($QQQ) use ($distributor_id) {
                    return $QQQ->where("dealer_id", $distributor_id);
                })
                ->where("product_id", $product_id)
                ->delete();

            /* produk suspend check */
            $product_suspended = DistributorProductSuspended::query()
                ->whereHas("distributorSuspended", function ($QQQ) use ($distributor_id) {
                    return $QQQ->where("dealer_id", $distributor_id);
                })
                ->where("is_suspended", "1")
                ->count();

            if ($product_suspended == 0) {
                $distributor_suspended->delete();
            }

            return $distributor_product;
        }

        return 0;
    }

    public function distributorStockContratBase($distributor_id, $product_id)
    {
        $distributor_contract = DistributorContract::query()
            ->where("dealer_id", $distributor_id)
            ->where(function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->whereDate("contract_start", "<=", Carbon::now()->format("Y-m-d"))
                            ->whereDate("contract_end", ">=", Carbon::now()->format("Y-m-d"));
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ->whereDate("contract_start", ">=", Carbon::now()->format("Y-m-d"));
                    });
            })
            ->orderBy("contract_start")
            ->get();

        $distributor_stock = SalesOrderV2::query()
            ->with([
                "invoice",
                "salesOrderDetail" => function ($QQQ) use ($product_id) {
                    return $QQQ->where("product_id", $product_id);
                },
            ])
            ->where("status", "confirmed")
            ->whereHas("salesOrderDetail", function ($QQQ) use ($product_id) {
                return $QQQ->where("product_id", $product_id);
            })
            ->where(function ($QQQ) use ($distributor_id, $distributor_contract) {
                return $QQQ
                    ->where(function ($QQQ) use ($distributor_id, $distributor_contract) {
                        return $QQQ
                            ->where("type", "1")
                            ->where("store_id", $distributor_id)
                            ->where("status", "confirmed")
                            ->whereHas("invoice", function ($QQQ) use ($distributor_contract) {
                                return $QQQ->where("created_at", ">=", Carbon::parse($distributor_contract->first()->contract_start)->format("Y-m-d"));
                            });
                    })
                    ->orWhere(function ($QQQ) use ($distributor_id, $distributor_contract) {
                        return $QQQ
                            ->where("type", "2")
                            ->where("store_id", $distributor_id)
                            ->where("status", "confirmed")
                            ->whereDate("created_at", ">=", $distributor_contract->first()->contract_start);
                    })
                    ->orWhere(function ($QQQ) use ($distributor_id, $distributor_contract) {
                        return $QQQ
                            ->where("type", "2")
                            ->where("distributor_id", $distributor_id)
                            ->where("status", "returned")
                            ->whereDate("created_at", ">=", $distributor_contract->first()->contract_start);
                    });
            })
            ->get();

        $distributor_sales = SalesOrderV2::query()
            ->with([
                "salesOrderDetail" => function ($QQQ) use ($product_id) {
                    return $QQQ->where("product_id", $product_id);
                },
            ])
            ->whereIn("status", ["confirmed", "submited", "draft", "proofed"])
            ->whereHas("salesOrderDetail", function ($QQQ) use ($product_id) {
                return $QQQ->where("product_id", $product_id);
            })
            ->where("type", "2")
            ->where("distributor_id", $distributor_id)
            ->whereDate("created_at", ">=", $distributor_contract->first()->contract_start)
            ->get();

        $distributor_adjusment = AdjustmentStock::query()
            ->where("opname_date", ">=", $distributor_contract->first()->contract_start)
            ->where("is_first_stock", "1")
            ->where("dealer_id", $distributor_id)
            ->where("product_id", $product_id)
            ->first();

        $stocks = collect();
        $sales = collect();
        collect($distributor_contract)->each(function ($contract) use ($distributor_stock, $distributor_sales, &$stocks, &$sales) {
            $stock_indirect_in_contract = $distributor_stock
                ->where("type", "2")
                ->where("created_at", ">=", $contract->contract_start)
                ->where("created_at", "<=", $contract->contract_end)
                ->values();

            $stock_direct_in_contract = $distributor_stock
                ->where("type", "1")
                ->where("invoice.created_at", ">=", $contract->contract_start)
                ->where("invoice.created_at", "<=", $contract->contract_end)
                ->values();

            $sales_inside_contract = $distributor_sales
                ->where("created_at", ">=", $contract->contract_start)
                ->where("created_at", "<=", $contract->contract_end)
                ->values();

            if ($stock_indirect_in_contract->count() > 0) {
                $stocks->push($stock_indirect_in_contract);
            }

            if ($stock_direct_in_contract->count() > 0) {
                $stocks->push($stock_direct_in_contract);
            }

            if ($sales_inside_contract->count() > 0) {
                $sales->push($sales_inside_contract);
            }
        });

        collect($stocks->flatten()->unique("id"))->each(function ($order) use (&$product_stock) {
            collect($order->salesOrderDetail)->each(function ($detail) use (&$product_stock) {
                if ($detail->returned_quantity) {
                    $product_stock += $detail->returned_quantity;
                } else {
                    $product_stock += $detail->settled_quantity;
                }
            });
        });

        // return $sales;

        $product_sales = 0;
        collect($sales->flatten()->unique("id"))->each(function ($order) use (&$product_sales) {
            $product_sales += collect($order->salesOrderDetail)->sum("quantity");
        });

        if ($distributor_adjusment) {
            $product_stock += $distributor_adjusment->product_in_warehouse + $distributor_adjusment->product_unreceived_by_distributor - $distributor_adjusment->product_undelivered_by_distributor;
        }

        return $product_stock - $product_sales;
    }

    /**
     * last opname
     */
    public function lastOpnameAccodingProductAndContract($distributor_id, $product_id)
    {
        $distributor_contracts = DistributorContract::query()
            ->where("dealer_id", $distributor_id)
            ->where(function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->whereDate("contract_start", "<=", Carbon::now()->format("Y-m-d"))
                            ->whereDate("contract_end", ">=", Carbon::now()->format("Y-m-d"));
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ->whereDate("contract_start", ">=", Carbon::now()->format("Y-m-d"));
                    });
            })
            ->orderBy("contract_start")
            ->get();

        if ($distributor_contracts->count() > 0) {
            $distributor_adjusment = AdjustmentStock::query()
                ->where("opname_date", ">=", $distributor_contracts->first()->contract_start)
                ->where("dealer_id", $distributor_id)
                ->where("product_id", $product_id)
                ->orderBy("opname_date", "desc")
                ->orderBy("created_at", "desc")
                ->first();

            return $distributor_adjusment;
        }

        return 0;

    }

    public function distributorActiveContract($distributor_id, $date = null)
    {
        $distributor_contract = DistributorContract::query()
            ->where("dealer_id", $distributor_id)
            ->where("contract_start", "<=", ($date ?? Carbon::now()->format("Y-m-d")))
            ->where("contract_end", ">=", ($date ?? Carbon::now()->format("Y-m-d")))
            ->orderBy("contract_start")
            ->first();

        return $distributor_contract;
    }

    public function distributorContractByDate($distributor_id, $date)
    {
        $distributor_contract = DistributorContract::query()
            ->where("dealer_id", $distributor_id)
            ->where(function ($QQQ) use ($contract_start, $date) {
                return $QQQ
                    ->where(function ($QQQ) use ($contract_start, $date) {
                        return $QQQ
                            ->whereDate("contract_start", "<=", $date)
                            ->whereDate("contract_end", ">=", $date);
                    });
            })
            ->orderBy("contract_start")
            ->first();

        return $distributor_contract;
    }

    public function distributorPrevoiusContract($distributor_id)
    {
        $distributor_contract = DistributorContract::query()
            ->where("dealer_id", $distributor_id)
            ->where(function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->whereDate("contract_end", "<", Carbon::now()->format("Y-m-d"));
                    });
            })
            ->orderBy("contract_end", "desc")
            ->first();

        return $distributor_contract;
    }

    public function pendingDistributorOrderDuringContract($distributor_id, $product_id)
    {
        $active_contract = $this->distributorActiveContract($distributor_id);
        if ($active_contract) {
            $sales_orders = SalesOrder::query()
                ->pendingDistributorSalesDuringContract($active_contract, $product_id)
                ->update([
                    "status" => "pending",
                ]);

            return $sales_orders;
        }
    }
}
