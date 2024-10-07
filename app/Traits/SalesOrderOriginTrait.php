<?php

namespace App\Traits;

use App\Traits\DistributorStock;
use Modules\SalesOrder\Entities\LogSalesOrderOrigin;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

/**
 *
 */
trait SalesOrderOriginTrait
{
    use DistributorStock;

    protected $parentColumn = 'parent_id';

    public function parent()
    {
        return $this->hasOne(static::class, "sales_order_id", "parent_id");
    }

    public function children()
    {
        return $this->hasMany(static::class, $this->parentColumn, "sales_order_id");
    }

    public function directChildren()
    {
        return $this->hasMany(static::class, "direct_id", "parent_id");
    }

    public function indirectChildren()
    {
        return $this->hasMany(static::class, "parent_id", "sales_order_id");
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function root()
    {
        return $this->parent
        ? $this->parent->root()
        : $this;
    }

    public function generateSalesOrderOrigin($sales_order)
    {        
        $log_sales_order_origin = LogSalesOrderOrigin::query()
            ->where("sales_order_id", $sales_order->id)
            ->first();

        /* if order doesnt have origin */
        if (!$log_sales_order_origin) {

            if ($sales_order->salesOrderDetail->count() > 0) {

                if ($sales_order->type == "2") {

                    collect($sales_order->salesOrderDetail)->each(function ($order_detail) use ($sales_order) {

                        /* rset settled_quantity to 0 */
                        $order_detail->settled_quantity = 0;
                        $order_detail->save();

                        /* delete existing origin if exist */
                        $delete_origin = SalesOrderOrigin::query()
                            ->where("sales_order_detail_id", $order_detail->id)
                            ->delete();

                        /* search origin that match for this order detail */
                        $origin = SalesOrderOrigin::query()
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

                        $product_child = collect();
                        if ($origin->count() > 0) {

                            $quantity = $order_detail->quantity - $order_detail->returned_quantity;

                            /* if current stock distributor not enough for sales */
                            if ($quantity > $origin->first()->stock_ready) {

                                $quantity_doesnt_settle = $quantity;

                                $origin->each(function ($origin) use ($quantity, &$quantity_doesnt_settle, $order_detail, $sales_order, &$product_child) {

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
                                        "sales_order_id" => $sales_order->id,
                                        "store_id" => $sales_order->store_id,
                                        "distributor_id" => $sales_order->distributor_id,
                                        "type" => $sales_order->type,
                                        "date" => confirmation_time($sales_order)->format("Y-m-d"),
                                        "sales_order_detail_id" => $order_detail->id,
                                        "quantity_order" => $quantity,
                                        "quantity_to_settle" => $quantity_to_settle, //100
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
                                    $active_contract = $this->distributorActiveContract($sales_order->distributor_id);

                                    if ($active_contract) {

                                        /* first stock distributor for this product */
                                        $first_stock = $this->adjustment_stock->query()
                                            ->where("dealer_id", $sales_order->distributor_id)
                                            ->where("product_id", $order_detail->product_id)
                                            ->where("opname_date", ">=", $active_contract->contract_start)
                                            ->where("opname_date", "<=", $active_contract->contract_end)
                                            ->where("is_first_stock", true)
                                            ->first();

                                        /* D1 proce for product */
                                        $product_price_D1 = $this->price->query()
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
                                            "sales_order_id" => $sales_order->id,
                                            "store_id" => $sales_order->store_id,
                                            "distributor_id" => $sales_order->distributor_id,
                                            "type" => $sales_order->type,
                                            "date" => confirmation_time($sales_order)->format("Y-m-d"),
                                            "sales_order_detail_id" => $order_detail->id,
                                            "quantity_order" => $quantity,
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
                                    "sales_order_id" => $sales_order->id,
                                    "store_id" => $sales_order->store_id,
                                    "distributor_id" => $sales_order->distributor_id,
                                    "type" => $sales_order->type,
                                    "date" => confirmation_time($sales_order)->format("Y-m-d"),
                                    "sales_order_detail_id" => $order_detail->id,
                                    "quantity_order" => $quantity,
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
                            $active_contract = $this->distributorActiveContract($sales_order->distributor_id);

                            /* first stock distributor for this product */
                            $first_stock = $this->adjustment_stock->query()
                                ->where("dealer_id", $sales_order->distributor_id)
                                ->where("product_id", $order_detail->product_id)
                                ->where("opname_date", ">=", $active_contract->contract_start)
                                ->where("opname_date", "<=", $active_contract->contract_end)
                                ->where("is_first_stock", true)
                                ->first();

                            /* D1 proce for product */
                            $product_price_D1 = $this->price->query()
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
                                "sales_order_id" => $sales_order->id,
                                "store_id" => $sales_order->store_id,
                                "distributor_id" => $sales_order->distributor_id,
                                "type" => $sales_order->type,
                                "date" => confirmation_time($sales_order)->format("Y-m-d"),
                                "sales_order_detail_id" => $order_detail->id,
                                "quantity_order" => $quantity,
                                "quantity_to_settle" => $current_stock,
                                "product_id" => $order_detail->product_id,
                                "stock_ready" => $current_stock,
                                "stock_out" => 0,
                                "is_splited_origin" => 0,
                                "confirmed_at" => confirmation_time($sales_order),
                                "lack_of_stock" => 0,
                            ]);

                            /* update order detail store */
                            $order_detail->settled_quantity += $quantity;
                            $order_detail->unit_price = $direct_price_for_this_product;
                            $order_detail->total = $direct_price_for_this_product * $order_detail->quantity;
                            $order_detail->save();
                        }

                        $product_child->each(function ($origin) use ($order_detail, &$nomor, &$data_origin, $quantity, $sales_order) {
                            $origin = (object) $origin;
                            $indirect_origin = SalesOrderOrigin::create([
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
                            ]);

                            /* save log */
                            $log = LogSalesOrderOrigin::firstOrCreate([
                                "sales_order_detail_id" => $order_detail->id,
                            ], [
                                "sales_order_id" => $order_detail->sales_order_id,
                                "type" => $sales_order->type,
                                "is_direct_set" => 1,
                                "is_direct_price_set" => 1,
                                "level" => 1,
                            ]);
                        });

                    });
                } else {

                    collect($sales_order->salesOrderDetail)->each(function ($order_detail) use($sales_order) {

                        /* rset settled_quantity to 0 */
                        $order_detail->settled_quantity = 0;
                        $order_detail->save();

                        $stock_ready = $order_detail->quantity;
                        $origin = SalesOrderOrigin::create([
                            "sales_order_detail_id" => $order_detail->id,
                            "sales_order_id" => $sales_order->id,
                            "direct_id" => $sales_order->id,
                            "sales_order_detail_direct_id" => $order_detail->id,
                            "parent_id" => $sales_order->id,
                            "sales_order_detail_parent_id" => $order_detail->id,
                            "product_id" => $order_detail->product_id,
                            "direct_price" => $order_detail->unit_price,
                            "quantity_from_origin" => $order_detail->quantity,
                            "current_stock" => $order_detail->stock,
                            "quantity_order" => $order_detail->quantity,
                            "type" => $sales_order->type,
                            "store_id" => $sales_order->store_id,
                            "lack_of_stock" => 0,
                            "stock_ready" => $stock_ready,
                            "is_splited_origin" => 0,
                            "stock_out" => 0,
                            "confirmed_at" => confirmation_time($sales_order),
                            "level" => 1,
                        ]);

                        /* update order detail store */
                        $order_detail->settled_quantity = $order_detail->quantity;
                        $order_detail->save();

                        /* save log */
                        $log = LogSalesOrderOrigin::firstOrCreate([
                            "sales_order_detail_id" => $order_detail->id,
                        ], [
                            "sales_order_id" => $order_detail->sales_order_id,
                            "type" => $sales_order->type,
                            "is_direct_set" => 1,
                            "is_direct_price_set" => 1,
                            "level" => 1,
                        ]);
                    });
                }

            }
        }
    }

    public function generateSalesOrderOriginFromFirstStock($first_stock)
    {
        $origin = SalesOrderOrigin::updateOrCreate([
            "product_id" => $first_stock->product_id,
            "direct_price" => $first_stock->stock_price,
            "quantity_from_origin" => $first_stock->real_stock,
            "current_stock" => $first_stock->real_stock,
            "quantity_order" => $first_stock->real_stock,
            "type" => "1",
            "store_id" => $first_stock->dealer_id,
            "lack_of_stock" => 0,
            "stock_ready" => $first_stock->real_stock,
            "first_stock" => $first_stock->real_stock,
            "is_splited_origin" => 0,
            "stock_out" => 0,
            "confirmed_at" => $first_stock->opname_date,
            "level" => 1,
        ]);

        return $origin;
    }
}
