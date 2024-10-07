<?php

namespace Modules\SalesOrder\Actions;

use Modules\DataAcuan\Entities\Price;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\SalesOrder\Entities\LogSalesOrderOrigin;
use Modules\Distributor\Entities\DistributorContract;
use Modules\SalesOrder\Actions\UpsertSalesOrderDetailAction;
use Modules\SalesOrder\Actions\UpsertSalesOrderOriginAction;

class GenerateSalesOrderOriginAction
{
    /**
     * regenerate sales order origin from distributor
     * pickup  during contract
     *
     *
     * @param DistributorContract $contract
     * @param [date] $date
     * @return void
     */
    public function __invoke(DistributorContract $active_contract, $date = null)
    {
        $sales_order_origin_action = new UpsertSalesOrderOriginAction();
        $sales_order_detail_action = new UpsertSalesOrderDetailAction();

        /**
         * DISTRIBUTOR PICKUP
         */
        Invoice::query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "salesOrderDetail" => function ($QQQ) {
                            return $QQQ->with([
                                "product",
                            ]);
                        },
                        "dealer",
                    ]);
                },
            ])
            ->whereHas("salesOrder", function ($QQQ) use ($active_contract, $date) {
                return $QQQ
                    ->whereHas("salesOrderDetail")
                    ->where("store_id", $active_contract->dealer_id)
                    ->distributorPickUpDuringContract($active_contract->dealer_id, ($date ? $date : $active_contract->contract_start), $active_contract->contract_end);
            })
            ->get()
            ->sortBy([
                ["created_at", "asc"],
            ])
            ->each(function ($invoice) use (
                &$sales_order_origin_action,
                &$sales_order_detail_action,
            ) {

                collect($invoice->salesOrder->salesOrderDetail)->each(function ($order_detail) use (
                    &$sales_order_origin_action,
                    &$sales_order_detail_action,
                    $invoice,
                ) {

                    /* reset settled quantity */
                    SalesOrderDetail::query()
                        ->where("id", $order_detail->id)
                        ->update([
                            "settled_quantity" => 0,
                        ]);

                    $stock_ready = $order_detail->quantity;
                    $payload = [
                        "sales_order_detail_id" => $order_detail->id,
                        "sales_order_id" => $invoice->salesOrder->id,
                        "direct_id" => $invoice->salesOrder->id,
                        "sales_order_detail_direct_id" => $order_detail->id,
                        "parent_id" => $invoice->salesOrder->id,
                        "sales_order_detail_parent_id" => $order_detail->id,
                        "product_id" => $order_detail->product_id,
                        "direct_price" => $order_detail->unit_price,
                        "quantity_from_origin" => $order_detail->quantity,
                        "current_stock" => $order_detail->stock,
                        "quantity_order" => $order_detail->quantity,
                        "type" => $invoice->salesOrder->type,
                        "store_id" => $invoice->salesOrder->store_id,
                        "lack_of_stock" => 0,
                        "stock_ready" => $stock_ready,
                        "is_splited_origin" => 0,
                        "stock_out" => 0,
                        "confirmed_at" => $invoice->created_at,
                        "level" => 1,
                    ];

                    /* store sales order origin */
                    $sales_order_origin_action($payload);

                    LogSalesOrderOrigin::updateOrCreate([
                        "sales_order_detail_id" => $order_detail->id,
                    ], [
                        "sales_order_id" => $invoice->salesOrder->id,
                        "type" => $invoice->salesOrder->type,
                        "is_direct_set" => 1,
                        "is_direct_price_set" => 1,
                        "level" => 1,
                    ]);

                    /* update settled quantity sales order detail */
                    $sales_order_detail_action(["settled_quantity" => $order_detail->quantity], $order_detail);
                });
            });

        /**
         * DISTRIBUTOR SALES
         */
        SalesOrderDetail::query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "dealer",
                        "subDealer",
                    ]);
                },
                "product",
            ])
            ->whereHas("salesOrder", function ($QQQ) use ($active_contract, $date) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("model", "1")
                                    ->whereHas("dealer", function ($QQQ) {
                                        return $QQQ->withTrashed();
                                    });
                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ
                                    ->where("model", "2")
                                    ->whereHas("subDealer", function ($QQQ) {
                                        return $QQQ->withTrashed();
                                    });
                            });
                    })
                    ->distributorSalesDuringContract($active_contract->dealer_id, ($date ? $date : $active_contract->contract_start), $active_contract->contract_end);
            })
            ->get()
            ->sortBy([
                ["salesOrder.date", "asc"],
                ["salesOrder.order_number", "asc"],
                ["salesOrder.store_id", "asc"],
                ["product_id", "asc"],
            ])
            ->each(function ($order_detail) use (
                &$sales_order_origin_action,
                &$sales_order_detail_action,
                $active_contract,
            ) {

                $order_detail->settled_quantity = 0;
                $order_detail->unit_price = 0;
                $order_detail->total = 0;
                $order_detail->save();

                /* search origin that match for this order detail */
                $origin = SalesOrderOrigin::query()
                    ->where("store_id", $order_detail->salesOrder->distributor_id)
                    ->where("product_id", $order_detail->product_id)
                    ->whereRaw("stock_ready > 0")
                    ->whereDate("confirmed_at", "<=", confirmation_time($order_detail->salesOrder))
                    ->orderBy("type")
                    ->take(6)
                    ->get()
                    ->sortBy([
                        ["confirmed_at", "asc"],
                        ["stock_ready", "desc"],
                    ]);

                $product_child = collect();
                if ($origin->count() > 0) {

                    $quantity = $order_detail->quantity - $order_detail->returned_quantity;

                    /* if current stock distributor not enough for sales */
                    if ($quantity > $origin->first()->stock_ready) {

                        $quantity_doesnt_settle = $quantity;

                        $origin->each(function ($origin) use ($quantity, &$product_child, &$quantity_doesnt_settle, $order_detail) {

                            if ($quantity_doesnt_settle >= $origin->stock_ready) {
                                $quantity_to_settle = $origin->stock_ready;
                            } else {
                                $quantity_to_settle = $quantity_doesnt_settle;
                            }

                            $product_child->push([
                                "origin_id" => $origin->id,
                                "direct_id" => $origin->direct_id,
                                "sales_order_detail_direct_id" => $origin->sales_order_detail_direct_id,
                                "parent_id" => $origin->direct_id,
                                "sales_order_detail_parent_id" => $origin->sales_order_detail_parent_id,
                                "parent_date" => $origin->confirmed_at,
                                "direct_price" => $origin->direct_price,
                                "current_stock" => $quantity_to_settle,
                                "sales_order_id" => $order_detail->salesOrder->id,
                                "store_id" => $order_detail->salesOrder->store_id,
                                "distributor_id" => $order_detail->salesOrder->distributor_id,
                                "type" => $order_detail->salesOrder->type,
                                "date" => confirmation_time($order_detail->salesOrder)->format("Y-m-d"),
                                "sales_order_detail_id" => $order_detail->id,
                                "quantity_order" => $quantity,
                                "quantity_to_settle" => $quantity_to_settle, //100
                                "product_id" => $order_detail->product_id,
                                "stock_ready" => $quantity_to_settle,
                                "stock_out" => 0,
                                "is_splited_origin" => true,
                                "confirmed_at" => confirmation_time($order_detail->salesOrder),
                                "lack_of_stock" => $quantity_doesnt_settle - $quantity_to_settle,
                            ]);

                            $quantity_doesnt_settle -= $quantity_to_settle;

                            /* update stock distributor on origin */
                            $origin->stock_ready = $origin->stock_ready - $quantity_to_settle;
                            $origin->stock_out = $origin->stock_out + $quantity_to_settle;
                            $origin->save();

                            /* update order detail store */
                            $order_detail->settled_quantity += $quantity_to_settle;
                            $order_detail->save();

                            if ($order_detail->settled_quantity >= $quantity) {
                                return false;
                            }
                        });

                        /**
                     * origin stock not found or stock out
                     * but all quantity not yet settled
                     */
                        if ($quantity_doesnt_settle > 0) {

                            /* first stock distributor for this product */
                            $first_stock = AdjustmentStock::query()
                                ->where("dealer_id", $order_detail->salesOrder->distributor_id)
                                ->where("product_id", $order_detail->product_id)
                                ->where("opname_date", ">=", $active_contract->contract_start)
                                ->where("opname_date", "<=", $active_contract->contract_end)
                                ->where("is_first_stock", true)
                                ->first();

                            /* D1 proce for product */
                            $product_price_D1 = Price::query()
                                ->where("product_id", $order_detail->product_id)
                                ->whereHas("agencyLevel")
                                ->orderBy("price")
                                ->first();

                            $direct_price_for_this_product = $first_stock ? $first_stock->stock_price : ($product_price_D1 ? $product_price_D1->prise : null);

                            $current_stock = $quantity_doesnt_settle;
                            $product_child->push([
                                "origin_id" => null,
                                "direct_id" => null,
                                "sales_order_detail_direct_id" => null,
                                "parent_id" => null,
                                "sales_order_detail_parent_id" => null,
                                "parent_date" => null,
                                "direct_price" => $direct_price_for_this_product,
                                "current_stock" => $current_stock,
                                "sales_order_id" => $order_detail->salesOrder->id,
                                "store_id" => $order_detail->salesOrder->store_id,
                                "distributor_id" => $order_detail->salesOrder->distributor_id,
                                "type" => $order_detail->salesOrder->type,
                                "date" => confirmation_time($order_detail->salesOrder)->format("Y-m-d"),
                                "sales_order_detail_id" => $order_detail->id,
                                "quantity_order" => $quantity,
                                "quantity_to_settle" => $current_stock,
                                "product_id" => $order_detail->product_id,
                                "stock_ready" => $current_stock,
                                "stock_out" => 0,
                                "is_splited_origin" => true,
                                "confirmed_at" => confirmation_time($order_detail->salesOrder),
                                "lack_of_stock" => 0,
                            ]);

                            /* update order detail store */
                            $order_detail->settled_quantity += $current_stock;
                            $order_detail->save();
                        }

                        /* set direct price on sales order detail using average direct price */
                        $avg_price = $product_child
                            ->where("sales_order_detail_id", $order_detail->id)
                            ->filter(fn($origin) => $origin["direct_price"])
                            ->pluck("direct_price")
                            ->avg();

                        $order_detail->unit_price = $avg_price;
                        $order_detail->total = $avg_price * $order_detail->quantity;
                        $order_detail->save();

                    } else {
                        $current_stock = $quantity;
                        $product_child->push([
                            "origin_id" => $origin->first()->id,
                            "direct_id" => $origin->first()->direct_id,
                            "sales_order_detail_direct_id" => $origin->first()->sales_order_detail_direct_id,
                            "parent_id" => $origin->first()->direct_id,
                            "sales_order_detail_parent_id" => $origin->first()->sales_order_detail_parent_id,
                            "parent_date" => $origin->first()->confirmed_at,
                            "direct_price" => $origin->first()->direct_price,
                            "current_stock" => $current_stock,
                            "sales_order_id" => $order_detail->salesOrder->id,
                            "store_id" => $order_detail->salesOrder->store_id,
                            "distributor_id" => $order_detail->salesOrder->distributor_id,
                            "type" => $order_detail->salesOrder->type,
                            "date" => confirmation_time($order_detail->salesOrder)->format("Y-m-d"),
                            "sales_order_detail_id" => $order_detail->id,
                            "quantity_order" => $quantity,
                            "quantity_to_settle" => $current_stock,
                            "product_id" => $order_detail->product_id,
                            "stock_ready" => $current_stock,
                            "stock_out" => 0,
                            "is_splited_origin" => 0,
                            "confirmed_at" => confirmation_time($order_detail->salesOrder),
                            "lack_of_stock" => 0,
                        ]);

                        /* update stock distributor on origin */
                        $origin->first()->stock_ready = $origin->first()->stock_ready - $quantity;
                        $origin->first()->stock_out = $origin->first()->stock_out + $quantity;
                        $origin->first()->save();

                        /* update order detail store */
                        $order_detail->settled_quantity += $quantity;
                        $order_detail->unit_price = $origin->first()->direct_price;
                        $order_detail->total = $origin->first()->direct_price * $order_detail->quantity;
                        $order_detail->save();
                    }
                }

                /* if there has no origin, or exist but no stock found */
                else {

                    $quantity = $order_detail->quantity - $order_detail->returned_quantity;

                    /* first stock distributor for this product */
                    $first_stock = AdjustmentStock::query()
                        ->where("dealer_id", $order_detail->salesOrder->distributor_id)
                        ->where("product_id", $order_detail->product_id)
                        ->where("opname_date", ">=", $active_contract->contract_start)
                        ->where("opname_date", "<=", $active_contract->contract_end)
                        ->where("is_first_stock", true)
                        ->first();

                    /* D1 proce for product */
                    $product_price_D1 = Price::query()
                        ->where("product_id", $order_detail->product_id)
                        ->whereHas("agencyLevel")
                        ->orderBy("price")
                        ->first();

                    $direct_price_for_this_product = $first_stock ? $first_stock->stock_price : ($product_price_D1 ? $product_price_D1->prise : null);

                    $current_stock = $quantity;
                    $product_child->push([
                        "origin_id" => null,
                        "direct_id" => null,
                        "sales_order_detail_direct_id" => null,
                        "parent_id" => null,
                        "sales_order_detail_parent_id" => null,
                        "parent_date" => null,
                        "direct_price" => $direct_price_for_this_product,
                        "current_stock" => $current_stock,
                        "sales_order_id" => $order_detail->salesOrder->id,
                        "store_id" => $order_detail->salesOrder->store_id,
                        "distributor_id" => $order_detail->salesOrder->distributor_id,
                        "type" => $order_detail->salesOrder->type,
                        "date" => confirmation_time($order_detail->salesOrder)->format("Y-m-d"),
                        "sales_order_detail_id" => $order_detail->id,
                        "quantity_order" => $quantity,
                        "quantity_to_settle" => $current_stock,
                        "product_id" => $order_detail->product_id,
                        "stock_ready" => $current_stock,
                        "stock_out" => 0,
                        "is_splited_origin" => 0,
                        "confirmed_at" => confirmation_time($order_detail->salesOrder),
                        "lack_of_stock" => 0,
                    ]);

                    /* update order detail store */
                    $order_detail->settled_quantity += $quantity;
                    $order_detail->unit_price = $direct_price_for_this_product;
                    $order_detail->total = $direct_price_for_this_product * $order_detail->quantity;
                    $order_detail->save();
                }

                $product_child->each(function ($origin) use (
                    &$sales_order_origin_action,
                    $order_detail,
                    $quantity,
                ) {
                    $origin = (object) $origin;

                    /* store sales order origin */
                    $payload = [
                        "sales_order_detail_id" => $origin->sales_order_detail_id,
                        "direct_id" => $origin->direct_id,
                        "sales_order_detail_direct_id" => $origin->sales_order_detail_direct_id,
                        "parent_id" => $origin->parent_id,
                        "sales_order_detail_parent_id" => $origin->sales_order_detail_parent_id,
                        "quantity_from_origin" => $origin->quantity_to_settle,
                        "lack_of_stock" => $origin->lack_of_stock,
                        "direct_price" => $origin->direct_price,
                        "origin_id" => $origin->origin_id,
                        "sales_order_id" => $origin->sales_order_id,
                        "product_id" => $origin->product_id,
                        "distributor_id" => $origin->distributor_id,
                        "current_stock" => $origin->current_stock,
                        "quantity_order" => $origin->quantity_order,
                        "type" => $origin->type,
                        "store_id" => $origin->store_id,
                        "stock_ready" => $origin->stock_ready,
                        "is_splited_origin" => $origin->is_splited_origin,
                        "stock_out" => $origin->stock_out,
                        "confirmed_at" => $origin->confirmed_at,
                        "level" => "2",
                    ];
                    $sales_order_origin_action($payload);

                    LogSalesOrderOrigin::updateOrCreate([
                        "sales_order_detail_id" => $order_detail->id,
                    ], [
                        "sales_order_id" => $order_detail->sales_order_id,
                        "type" => $order_detail->salesOrder->type,
                        "is_direct_set" => 1,
                        "is_direct_price_set" => 1,
                        "level" => 1,
                    ]);

                });

            })
            ->groupBy("sales_order_id")
            ->each(function ($order_detail_per_order, $sales_order_id) {
                $sales_order = SalesOrder::find($sales_order_id);

                if ($sales_order) {
                    $sales_order->total = collect($order_detail_per_order)->sum("total");
                    $sales_order->sub_total = collect($order_detail_per_order)->sum("total");
                    $sales_order->save();
                }
            });
    }
}
