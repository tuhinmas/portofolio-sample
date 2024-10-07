<?php

namespace Modules\SalesOrder\Actions\Order\Origin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Price;
use Modules\Distributor\Entities\DistributorContract;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\SalesOrder\Entities\LogSalesOrderOrigin;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

/**
 * generate direct and isdirect sales origin
 */
class GenerateSalesOriginFromOrderDetailAction
{
    protected static $product_child;

    public function __construct(
        protected SalesOrder $sales_order,
        protected AdjustmentStock $adjustment_stock,
        protected SalesOrderOrigin $sales_order_origin,
        protected LogSalesOrderOrigin $log_sales_order_origin,
    ) {
        self::$product_child = collect();
    }

    public function __invoke(DistributorContract $active_contract, SalesOrderDetail $order_detail, ?string $type = null)
    {

        if ($order_detail->quantity - $order_detail->returned_quantity <= 0 || !$active_contract) {
            return $order_detail;
        }

        self::$product_child = collect();
        $sales_order = $this->sales_order
            ->with([
                "invoice",
            ])
            ->consideredOrder()
            ->findOrFail($order_detail->sales_order_id);

        /*
        |--------------------------------------
        | SALES RETURN AND PICKUP RETURN
        |----------------------------------
         */
        if ($type == "return") {
            if (!$order_detail->returned_quantity) {
                return $order_detail;
            }

            switch ($sales_order->type) {
                case 1:
                    if ($order_detail->returned_quantity <= 0) {
                        break;
                    }

                    $returned_quantity = $order_detail->returned_quantity;
                    $last_sales_before_return = $this->sales_order->query()
                        ->distributorSalesDuringContract($sales_order->store_id, $active_contract->contract_start, $active_contract->contract_end)
                        ->where("date", "<=", $sales_order->return)
                        ->whereHas("salesOrderDetail", function ($QQQ) use ($order_detail) {
                            return $QQQ->where("product_id", $order_detail->product_id);
                        })
                        ->orderBy("date", "desc")
                        ->first();

                    $order_detail->load([
                        "salesOrder.invoice",
                    ]);

                    $stock_to_reduce = $this->sales_order_origin->query()
                        ->whereDate("confirmed_at", ">=", $active_contract->contract_start)
                        ->whereDate("confirmed_at", "<=", $active_contract->contract_end)
                        ->where("store_id", $active_contract->dealer_id)
                        ->whereRaw("stock_ready > 0")
                        ->where("product_id", $order_detail->product_id)
                        ->limit(15)
                        ->orderBy("confirmed_at")
                        ->get()
                        ->each(function ($origin) use ($order_detail, &$returned_quantity) {
                            if ($origin->stock_ready >= $returned_quantity) {
                                $origin->stock_ready -= $returned_quantity;
                                $origin->stock_out += $returned_quantity;
                                $origin->note = "reduction from return pickup";
                                $origin->save();
                                $origin->refresh();
                                $returned_quantity = 0;
                                return false;
                            }

                            $returned_quantity -= $origin->stock_ready;
                            $origin->stock_ready = 0;

                            /* fill stock out as full */
                            $origin->stock_out = $origin->quantity_from_origin;
                            $origin->note = "reduction from return pickup";
                            $origin->save();

                            $origin->refresh();
                            if ($returned_quantity <= 0) {
                                return false;
                            }
                        });

                    return $stock_to_reduce;
                    break;
                default:
                    $origin = $this->sales_order_origin->create([
                        "sales_order_detail_return_id" => $order_detail->id,
                        "product_id" => $order_detail->product_id,
                        "direct_price" => $order_detail->unit_price,
                        "quantity_from_origin" => $order_detail->returned_quantity,
                        "current_stock" => $order_detail->stock,
                        "quantity_order" => $order_detail->returned_quantity,
                        "type" => 4, // 1 => direct, 2 => indirect, 3 => direct return 4 => indirect return
                        "store_id" => $sales_order->distributor_id,
                        "lack_of_stock" => 0,
                        "stock_ready" => $order_detail->returned_quantity,
                        "is_splited_origin" => 0,
                        "stock_out" => 0,
                        "confirmed_at" => $order_detail->salesOrder->return,
                        "level" => 1,
                        "is_fee_counted" => false,
                        "is_point_counted" => false,
                        "is_returned" => true,
                    ]);
                    return $origin;
                    break;
            }

            return $order_detail;
        }

        $origin = DB::table('sales_order_origins')
            ->whereNull("deleted_at")
            ->where("sales_order_detail_id", $order_detail->id)
            ->first();

        /* origin was exist, no need to generate again */
        if ($origin) {
            return $order_detail;
        }

        /**
         * indirect sales origin
         */
        if ($sales_order->type == "2") {

            $order_detail->settled_quantity = 0;
            $order_detail->unit_price = 0;
            $order_detail->total = 0;
            $order_detail->save();

            /**
             * search origin that match for this order detail
             * means, distributor stock less than nota date
             */
            $origin = $this->sales_order_origin->query()
                ->where("store_id", $sales_order->distributor_id)
                ->where("product_id", $order_detail->product_id)
                ->whereRaw("stock_ready > 0")
                ->whereDate("confirmed_at", "<=", confirmation_time($sales_order))
                ->take(6)
                ->get()
                ->sortBy([
                    ["confirmed_at", "asc"],
                    ["stock_ready", "desc"],
                ]);

            /* stock less then nota date found */
            if ($origin->count() > 0) {
                $quantity = $order_detail->quantity - $order_detail->returned_quantity;

                /**
                 * if current stock distributor not enough for sales.
                 * using first() cause it is the bigger stock ready,
                 * as you can see it was sorted by stock
                 * descending, the purpose of this sort
                 * is to minimalize splited indirect
                 * origin
                 */
                if ($quantity > $origin->first()->stock_ready) {

                    $quantity_doesnt_settle = $quantity;
                    $quantity_doesnt_settle = self::quantityDoesNotSettled($origin, $sales_order, $order_detail, $quantity_doesnt_settle);

                    /**
                     * origin stock not found or stock out
                     * but all quantity not yet settled,
                     * use stock from higher then nota
                     * date, if still not enough
                     * then indirect origin
                     * store without origin
                     */
                    if ($quantity_doesnt_settle > 0) {
                        $origin_higher_date = self::stockHigherThanNotaDate($sales_order, $order_detail);
                        if ($origin_higher_date->count() > 0) {
                            $quantity_doesnt_settle = self::quantityDoesNotSettled($origin, $sales_order, $order_detail, $quantity_doesnt_settle);
                            if ($quantity_doesnt_settle > 0) {
                                self::indirectDoesntHaveOrigin($active_contract, $sales_order, $order_detail, $quantity_doesnt_settle);
                            }
                        } else {
                            self::indirectDoesntHaveOrigin($active_contract, $sales_order, $order_detail, $quantity_doesnt_settle);
                        }
                    }
                }

                /**
                 * stock is sufficient and origin
                 * will not splitted
                 */
                else {
                    $current_stock = $quantity;
                    self::$product_child->push([
                        "origin_id" => $origin->first()->id,
                        "direct_id" => $origin->first()->direct_id,
                        "sales_order_detail_direct_id" => $origin->first()->sales_order_detail_direct_id,
                        "parent_id" => $origin->first()->direct_id,
                        "sales_order_detail_parent_id" => $origin->first()->sales_order_detail_parent_id,
                        "parent_date" => $origin->first()->confirmed_at,
                        "direct_price" => $origin->first()->direct_price,
                        "current_stock" => $current_stock,
                        "sales_order_id" => $sales_order->id,
                        "store_id" => $sales_order->store_id,
                        "distributor_id" => $sales_order->distributor_id,
                        "type" => $sales_order->type,
                        "date" => confirmation_time($sales_order)->format("Y-m-d"),
                        "sales_order_detail_id" => $order_detail->id,
                        "quantity_order" => $order_detail->quantity - $order_detail->returned_quantity,
                        "quantity_to_settle" => $current_stock,
                        "product_id" => $order_detail->product_id,
                        "stock_ready" => $current_stock,
                        "stock_out" => 0,
                        "is_splited_origin" => 0,
                        "confirmed_at" => confirmation_time($sales_order),
                        "lack_of_stock" => 0,
                    ]);

                    /* update stock distributor on origin */
                    $origin->first()->stock_ready = $origin->first()->stock_ready - $quantity;
                    $origin->first()->stock_out = $origin->first()->stock_out + $quantity;
                    $origin->first()->save();
                }
            }

            /**
             * if there has no origin less than nota date,
             * get stock from higher nota date, if
             * still not found store as no origin
             * found
             */
            else {

                $origin_higher_date = self::stockHigherThanNotaDate($sales_order, $order_detail);
                $quantity_doesnt_settle = $order_detail->quantity - $order_detail->returned_quantity;
                if ($origin_higher_date->count() > 0) {
                    $quantity_doesnt_settle = self::quantityDoesNotSettled($origin, $sales_order, $order_detail, $quantity_doesnt_settle);
                    if ($quantity_doesnt_settle > 0) {
                        self::indirectDoesntHaveOrigin($active_contract, $sales_order, $order_detail, $quantity_doesnt_settle);
                    }
                } else {
                    self::indirectDoesntHaveOrigin($active_contract, $sales_order, $order_detail, $quantity_doesnt_settle);
                }
            }

            self::$product_child->each(function ($origin) use ($sales_order, $order_detail) {
                $origin = (object) $origin;
                $this->sales_order_origin->create([
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
                    "quantity_order" => $order_detail->quantity - $order_detail->returned_quantity,
                    "type" => $origin->type,
                    "store_id" => $origin->store_id,
                    "stock_ready" => $origin->stock_ready,
                    "is_splited_origin" => $origin->is_splited_origin,
                    "stock_out" => $origin->stock_out,
                    "confirmed_at" => $origin->confirmed_at,
                    "level" => "2",
                ]);

                $this->log_sales_order_origin->firstOrCreate([
                    "sales_order_detail_id" => $order_detail->id,
                ], [
                    "sales_order_id" => $order_detail->sales_order_id,
                    "type" => $sales_order->type,
                    "is_direct_set" => 1,
                    "is_direct_price_set" => 1,
                    "level" => 1,
                ]);
            });

            /**
             * ----------------------------------------------------------------------
             * since 2024-08-05, the rule for indirect product price changed,
             * unit_price = (qty_settle * direct_proce) / qty_order
             */
            $avg_price = self::$product_child
                ->where("sales_order_detail_id", $order_detail->id)
                ->filter(fn($origin) => $origin["direct_price"])
                ->map(function ($product) {
                    $product["nominal_use_direct_price"] = $product["direct_price"] * $product["quantity_to_settle"];
                    return $product;
                })
                ->sum("nominal_use_direct_price") / ($order_detail->quantity - $order_detail->returned_quantity);

            $order_detail->unit_price = $avg_price;
            $order_detail->total = $avg_price * ($order_detail->quantity - $order_detail->returned_quantity);
            $order_detail->settled_quantity = self::$product_child
                ->where("sales_order_detail_id", $order_detail->id)
                ->sum("quantity_to_settle");

            $order_detail->save();
            return $order_detail;
        }

        /* generate direct as origin */
        else {
            $this->sales_order_origin->firstOrCreate([
                "sales_order_detail_id" => $order_detail->id,
            ], [
                "sales_order_id" => $sales_order->id,
                "direct_id" => $sales_order->id,
                "sales_order_detail_direct_id" => $order_detail->id,
                "parent_id" => $sales_order->id,
                "sales_order_detail_parent_id" => $order_detail->id,
                "product_id" => $order_detail->product_id,
                "direct_price" => $order_detail->unit_price,
                "quantity_from_origin" => $order_detail->quantity,
                "current_stock" => $order_detail->quantity,
                "quantity_order" => $order_detail->quantity,
                "type" => $sales_order->type,
                "store_id" => $sales_order->store_id,
                "lack_of_stock" => 0,
                "stock_ready" => $order_detail->quantity,
                "is_splited_origin" => 0,
                "stock_out" => 0,
                "confirmed_at" => confirmation_time($sales_order),
                "level" => 1,
            ]);

            /* update order detail store */
            $order_detail->settled_quantity = $order_detail->quantity;
            $order_detail->save();

            /* save log */
            $this->log_sales_order_origin->firstOrCreate([
                "sales_order_detail_id" => $order_detail->id,
            ], [
                "sales_order_id" => $order_detail->sales_order_id,
                "type" => $sales_order->type,
                "is_direct_set" => 1,
                "is_direct_price_set" => 1,
                "level" => 1,
            ]);
        }
    }

    public static function indirectPriceGetter($active_contract, $sales_order, $order_detail)
    {
        if ($active_contract) {

            /* first stock distributor for this product */
            $first_stock = AdjustmentStock::query()
                ->where("dealer_id", $sales_order->distributor_id)
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

            $direct_price_for_this_product = $first_stock ? $first_stock->stock_price : ($product_price_D1 ? $product_price_D1->price : 0);
            return $direct_price_for_this_product;
        }

        return 0;
    }

    /**
     * populate origin to quantity order
     *
     * @param [type] $origins
     * @return void
     */
    public static function quantityDoesNotSettled(
        Collection $origins,
        $sales_order,
        $order_detail,
        $quantity_doesnt_settle,
    ) {
        $origins->each(function ($origin) use (
            &$quantity_doesnt_settle,
            $order_detail,
            $sales_order,
        ) {

            /* check quantity to settle according quantityu has settled */
            $quantity_to_settle = $quantity_doesnt_settle;
            if ($quantity_doesnt_settle >= $origin->stock_ready) {
                $quantity_to_settle = $origin->stock_ready;
            }

            self::$product_child->push([
                "origin_id" => $origin->id,
                "direct_id" => $origin->direct_id,
                "sales_order_detail_direct_id" => $origin->sales_order_detail_direct_id,
                "parent_id" => $origin->direct_id,
                "sales_order_detail_parent_id" => $origin->sales_order_detail_parent_id,
                "parent_date" => $origin->confirmed_at,
                "direct_price" => $origin->direct_price,
                "current_stock" => $quantity_to_settle,
                "sales_order_id" => $sales_order->id,
                "store_id" => $sales_order->store_id,
                "distributor_id" => $sales_order->distributor_id,
                "type" => $sales_order->type,
                "date" => confirmation_time($sales_order)->format("Y-m-d"),
                "sales_order_detail_id" => $order_detail->id,
                "quantity_order" => $order_detail->quantity - $order_detail->returned_quantity,
                "quantity_to_settle" => $quantity_to_settle,
                "product_id" => $order_detail->product_id,
                "stock_ready" => $quantity_to_settle,
                "stock_out" => 0,
                "is_splited_origin" => true,
                "confirmed_at" => confirmation_time($sales_order),
                "lack_of_stock" => $quantity_doesnt_settle - $quantity_to_settle,
            ]);

            $quantity_doesnt_settle -= $quantity_to_settle;

            /* update stock distributor on origin */
            $origin->stock_ready = $origin->stock_ready - $quantity_to_settle;
            $origin->stock_out = $origin->stock_out + $quantity_to_settle;
            $origin->save();

            /* break if quantity was settled */
            if ($quantity_doesnt_settle <= 0) {
                return false;
            }

            /* update order detail store */
            $order_detail->settled_quantity += $quantity_to_settle;
            $order_detail->save();

        });

        return $quantity_doesnt_settle;
    }

    /**
     * store origin as  no origin
     */
    public static function indirectDoesntHaveOrigin($active_contract, $sales_order, $order_detail, $quantity_doesnt_settle)
    {
        $direct_price_for_this_product = self::indirectPriceGetter($active_contract, $sales_order, $order_detail);
        $current_stock = $quantity_doesnt_settle;
        self::$product_child->push([
            "origin_id" => null,
            "direct_id" => null,
            "sales_order_detail_direct_id" => null,
            "parent_id" => null,
            "sales_order_detail_parent_id" => null,
            "parent_date" => null,
            "direct_price" => $direct_price_for_this_product,
            "current_stock" => $current_stock,
            "sales_order_id" => $sales_order->id,
            "store_id" => $sales_order->store_id,
            "distributor_id" => $sales_order->distributor_id,
            "type" => $sales_order->type,
            "date" => confirmation_time($sales_order)->format("Y-m-d"),
            "sales_order_detail_id" => $order_detail->id,
            "quantity_order" => $order_detail->quantity - $order_detail->returned_quantity,
            "quantity_to_settle" => $current_stock,
            "product_id" => $order_detail->product_id,
            "stock_ready" => $current_stock,
            "stock_out" => 0,
            "is_splited_origin" => true,
            "confirmed_at" => confirmation_time($sales_order),
            "lack_of_stock" => 0,
        ]);

        /* update order detail store */
        $order_detail->settled_quantity += $current_stock;
        $order_detail->save();
    }

    /**
     * stock date that higher than nota date
     * according new rule 2024-01-19
     *
     * @param [type] $sales_order
     * @param [type] $order_detail
     * @return void
     */
    public static function stockHigherThanNotaDate($sales_order, $order_detail)
    {
        return SalesOrderOrigin::query()
            ->where("store_id", $sales_order->distributor_id)
            ->where("product_id", $order_detail->product_id)
            ->whereRaw("stock_ready > 0")
            ->whereDate("confirmed_at", ">=", confirmation_time($sales_order))
            ->take(6)
            ->get()
            ->sortBy([
                ["confirmed_at", "asc"],
                ["stock_ready", "desc"],
            ]);

    }
}
