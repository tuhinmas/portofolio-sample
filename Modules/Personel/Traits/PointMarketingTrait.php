<?php

namespace Modules\Personel\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Contracts\Activity;

/**
 *
 */
trait PointMarketingTrait
{
    public function recalcultePointMarketingPerProduct($personel_id, $year, $sales_order_ids = null)
    {
        /**
         * current point products
         */
        $product_point_references = DB::table('point_products')
            ->whereNull("deleted_at")
            ->where("year", $year)
            ->get();

        /**
         * sales order marketing
         */
        $sales_orders = $this->sales_order->query()
            ->with([
                "sales_order_detail" => function ($QQQ) {
                    return $QQQ->with("sales_order");
                },
                "dealer",
            ])
            ->pointMarketingPerProduct($personel_id, $year)
            ->when($sales_order_ids, function ($QQQ) use ($sales_order_ids) {
                return $QQQ->whereIn("id", $sales_order_ids);
            })
            ->get()
            ->reject(function ($order) {

                /**
             * distributor check, distributor does not
             * get point at all. Only distributor
             * which has active contrack
             */
                if ($order->model == "1") {
                    if ($order->dealer) {
                        $active_contract = $this->distributorActiveContract($order->store_id);

                        if ($active_contract) {
                            $order->sales_order_detail->each(function ($order_detail) {
                                $detail = $this->sales_order_detail->query()
                                    ->where("id", $order_detail->id)
                                    ->update([
                                        "marketing_point" => 0,
                                    ]);
                            });

                            return $order;
                        }

                    }
                }
            })
            ->pluck("sales_order_detail")
            ->flatten()
            ->each(function ($order_detail) {
                $order_detail->marketing_point = 0;
                $order_detail->save();
            })
            ->filter(function ($order_detail) use ($product_point_references) {
                return in_array($order_detail->product_id, $product_point_references->pluck("product_id")->toArray());
            })
            ->each(function ($order_detail) use ($year) {

                $quantity = $order_detail->quantity -  $order_detail->returned_quantity;
                $point = 0;

                /* point product this year */
                $point_product = collect($order_detail->pointProductAllYear)->where("year", $year)->where('quantity', '<=', $quantity)->sortByDesc('minimum_quantity')->values();

                collect($point_product)->each(function ($point_per_quantity) use ($year, &$point, $order_detail, &$quantity, &$point_detail) {
                    $corresponding_point = floor($quantity / $point_per_quantity->minimum_quantity);
                    $modulo = $quantity % $point_per_quantity->minimum_quantity;
                    $point += $corresponding_point * $point_per_quantity->point;
                    $quantity = $modulo;
                });

                if (is_affected_from_return($order_detail->sales_order)) {
                    $point = 0;
                }

                $order_detail->marketing_point = $point;
                $order_detail->save();

                $log = $this->log_worker_sales_point->updateOrCreate([
                    "sales_order_id" => $order_detail->sales_order_id,
                ], [
                    "type" => $order_detail->sales_order->type,
                    "checked_at" => now(),
                ]
                );
            })
            ->values();

        return $sales_orders;
    }

    public function recalcultePointMarketingTotal($personel_id, $year)
    {
        $product_point_references = DB::table('point_products')
            ->whereNull("deleted_at")
            ->where("year", $year)
            ->get();

        /**
         * sales order marketing
         */
        $sales_orders = $this->sales_order->query()
            ->with([
                "logWorkerSalesPoint",
            ])
            ->pointMarketingTotal($personel_id, $year)
            ->get();

        $point_marketing_total = $this->sales_order_detail->query()
            ->whereIn("sales_order_id", $sales_orders->pluck("id")->toArray())
            ->whereIn("product_id", $product_point_references->pluck("product_id")->toArray())
            ->get();

        $sum_point_marketing_total = $point_marketing_total->sum("marketing_point");

        /**
         * update log point marketing
         */
        $point_marketing_total
            ->groupBy("sales_order_id")
            ->each(function ($order_detail) use ($personel_id, $year) {
                $log_worker_point_marketing = $this->log_worker_point_marketing->firstOrCreate([
                    "sales_order_id" => $order_detail[0]->sales_order_id,
                ], [
                    "is_count" => "1",
                    "is_active" => "0",
                ]);
            });

        $point_marketing = $this->point_marketing->query()
            ->where('personel_id', $personel_id)
            ->where('year', $year)
            ->first();

        $old_point = [
            "personel_id" => $personel_id,
            "marketing_point_total" => $point_marketing?->marketing_point_total,
            "marketing_point_active" => $point_marketing?->marketing_point_active,
            "marketing_point_adjustment" => $point_marketing?->marketing_point_adjustment,
            "marketing_point_redeemable" => $point_marketing?->marketing_point_redeemable,
            "status" => $point_marketing?->status,
            "year" => $point_marketing?->year,
        ];

        $point_marketing_adjustment = $this->marketing_point_adjustment->query()
            ->whereHas("PointMarketing", function ($QQQ) use ($personel_id, $year) {
                return $QQQ
                    ->where("personel_id", $personel_id)
                    ->where("year", $year);
            })
            ->get()
            ->sum("adjustment_poin");

        if ($point_marketing) {
            $point_marketing->marketing_point_total = $sum_point_marketing_total;
            $point_marketing->save();
        } else {
            $point_marketing = $this->point_marketing->updateOrCreate([
                "personel_id" => $personel_id,
                "year" => $year,
            ], [
                "marketing_point_total" => $sum_point_marketing_total,
                "marketing_point_active" => 0,
                "marketing_point_adjustment" => $point_marketing_adjustment,
                "marketing_point_redeemable" => 0,
                "status" => "not_redeemed",
            ]);
        }

        $test = activity()
            ->causedBy(auth()->id())
            ->performedOn($point_marketing)
            ->withProperties([
                "old" => $old_point,
                "attributes" => $point_marketing,
            ])
            ->tap(function (Activity $activity) {
                $activity->log_name = 'sync';
            })
            ->log('marketing point syncronize');

        return $point_marketing;

    }

    public function recalcultePointMarketingActive($personel_id, $year)
    {
        $product_point_references = DB::table('point_products')
            ->whereNull("deleted_at")
            ->where("year", $year)
            ->get();

        /* maximum payment days */
        $maximum_settle_days = maximum_settle_days(now()->format("Y"));

        /**
         * sales order marketing
         */
        $sales_orders = $this->sales_order->query()
            ->with([
                "invoice",
                "logWorkerSalesPoint",
            ])
            ->pointMarketingActive($personel_id, $year)
            ->get()
            ->filter(function ($order) {

                /* check order is considered active or not */
                $is_order_considerd_active = $this->isOrderActivePoint($order);

                if ($is_order_considerd_active) {
                    return $order;
                }
            })
            ->values();

        $point_marketing_active = $this->sales_order_detail->query()
            ->with([
                "sales_order",
            ])
            ->whereIn("sales_order_id", $sales_orders->pluck("id")->toArray())
            ->whereIn("product_id", $product_point_references->pluck("product_id")->toArray())
            ->get();

        /**
         * update log point marketing
         */
        $point_marketing_active
            ->groupBy("sales_order_id")
            ->each(function ($order_detail) use ($personel_id, $year) {

                $log_worker_point_marketing_active = $this->log_worker_point_marketing_active->firstOrCreate([
                    "sales_order_id" => $order_detail[0]->sales_order_id,
                ]);

                $log_worker_point_marketing = $this->log_worker_point_marketing->updateOrCreate([
                    "sales_order_id" => $order_detail[0]->sales_order_id,
                ], [
                    "is_count" => "1",
                    "is_active" => "1",
                ]);
            });

        /* adjustment point */
        $point_marketing_adjustment = $this->marketing_point_adjustment->query()
            ->with("PointMarketing")
            ->whereHas("PointMarketing", function ($QQQ) use ($personel_id, $year) {
                return $QQQ
                    ->where("personel_id", $personel_id)
                    ->where("year", $year);
            })
            ->get()
            ->sum("adjustment_poin");

        $point_marketing = $this->point_marketing->query()
            ->where('personel_id', $personel_id)
            ->where('year', $year)
            ->first();

        $old_point = [
            "personel_id" => $personel_id,
            "marketing_point_total" => $point_marketing?->marketing_point_total,
            "marketing_point_active" => $point_marketing?->marketing_point_active,
            "marketing_point_adjustment" => $point_marketing?->marketing_point_adjustment,
            "marketing_point_redeemable" => $point_marketing?->marketing_point_redeemable,
            "status" => $point_marketing?->status,
            "year" => $point_marketing?->year,
        ];

        if ($point_marketing) {
            $point_marketing->marketing_point_active = $point_marketing_active->sum("marketing_point");
            $point_marketing->marketing_point_adjustment = $point_marketing_adjustment;
            $point_marketing->marketing_point_redeemable = $point_marketing_active->sum("marketing_point") + $point_marketing_adjustment;
            $point_marketing->save();
        } else {
            $point_marketing = $this->point_marketing->updateOrCreate([
                "personel_id" => $personel_id,
                "year" => $year,
            ], [
                "marketing_point_total" => $point_marketing_active->sum("marketing_point"),
                "marketing_point_active" => $point_marketing_active->sum("marketing_point"),
                "marketing_point_adjustment" => $point_marketing_adjustment,
                "marketing_point_redeemable" => $point_marketing_active->sum("marketing_point"),
                "status" => "not_redeemed",
            ]);
        }

        $test = activity()
            ->causedBy(auth()->id())
            ->performedOn($point_marketing)
            ->withProperties([
                "old" => $old_point,
                "attributes" => $point_marketing,
            ])
            ->tap(function (Activity $activity) {
                $activity->log_name = 'sync';
            })
            ->log('marketing point syncronize');

        return $point_marketing;
    }

    public function marketingPointPerProductCalculator($sales_order, $active_contract = null)
    {
        /**
         * distributor check, distributor does not
         * get point at all. Only distributor
         * which has active contrack
         */
        if ($sales_order->model == "1") {
            if ($sales_order->dealer) {
                if (!$active_contract) {
                    $active_contract = $this->distributorActiveContract($sales_order->dealer->id);
                }

                if ($active_contract) {
                    $sales_order->sales_order_detail->each(function ($order_detail) {
                        $detail = $this->sales_order_detail->query()
                            ->where("id", $order_detail->id)
                            ->update([
                                "marketing_point" => 0,
                            ]);
                    });

                    return "active_contract found";
                }

            }
        }

        if (confirmation_time($sales_order)) {
            $log = $this->log_worker_sales_point->updateOrCreate([
                "sales_order_id" => $sales_order->id,
            ], [
                "type" => $sales_order->type,
                "checked_at" => now()]
            );

            if (is_affected_from_return($sales_order)) {
                $sales_order->sales_order_detail->each(function ($order_detail) {
                    $order_detail->marketing_point = 0;
                    $order_detail->save();
                });

                return "order affected from return";
            }

            $year = confirmation_time($sales_order)->format("Y");
            $point_detail = collect();

            $sales_order->sales_order_detail->each(function ($order_detail) use (&$point_detail, $year) {

                $quantity = $order_detail->quantity - $order_detail->returned_quantity;
                $point = 0;

                /* point product this year */
                $point_product = $this->point_product->query()
                    ->where("year", $year)
                    ->where('minimum_quantity', '<=', $quantity)
                    ->where("product_id", $order_detail->product_id)
                    ->get()
                    ->sortByDesc('minimum_quantity')
                    ->values();

                collect($point_product)->each(function ($point_per_quantity) use ($year, &$point, $order_detail, &$quantity, &$point_detail) {
                    $corresponding_point = floor($quantity / $point_per_quantity->minimum_quantity);
                    $modulo = $quantity % $point_per_quantity->minimum_quantity;
                    $point += $corresponding_point * $point_per_quantity->point;
                    $point_detail->push(collect([
                        "sales_order-detail_id" => $order_detail->id,
                        "product_id" => $order_detail->product_id,
                        "minimum_quantity" => $point_per_quantity->minimum_quantity,
                        "corresponding_point" => $corresponding_point,
                        "modulo" => $modulo,
                        "point" => $point,
                        "year" => $year,
                    ]));

                    $quantity = $modulo;
                });

                $detail = $this->sales_order_detail->query()
                    ->where("id", $order_detail->id)
                    ->first();

                $detail->marketing_point = $point;
                $detail->save();
            });
        }
    }

    /**
     * check order is considred active or not
     *
     * @param [type] $order
     * @return boolean
     */
    public function isOrderActivePoint($order)
    {
        if ($order->personel_id && empty($order->afftected_by_return) && !is_return_order_exist($order->store_id, $order->personel_id, confirmation_time($order)->format("Y"), confirmation_time($order)->quarter)) {

            /**
             * order will be condidered active if settle in the same year with confirmation
             * date or in the different year but less then 60 days (according
             * data reference)
             */
            if ($order->type == 1 && $order->invoice->payment_status == "settle") {

                $order_year = confirmation_time($order)->format("Y");

                /* maximum payment in the different year */
                $maximum_settle_days = maximum_settle_days($order_year);
                $is_point_counted_as_active = false;

                /**
                 * order will be condidered active if settle in the same year with confirmation
                 * date or in the different year but less then 60 days (according
                 * data reference)
                 */
                if ($order->invoice->last_payment == "-") {
                    $is_point_counted_as_active = true;
                } else if (Carbon::parse($order->invoice->last_payment)->format("Y") == Carbon::parse($order->invoice->created_at)->format("Y")) {
                    $is_point_counted_as_active = true;
                } elseif (Carbon::parse($order->invoice->last_payment)->format("Y") != Carbon::parse($order->invoice->created_at)->format("Y")) {
                    if ($order->invoice->created_at->diffInDays($order->invoice->last_payment, false) <= ($maximum_settle_days ? $maximum_settle_days : 60)) {
                        $is_point_counted_as_active = true;
                    }
                }

                return $is_point_counted_as_active;
            }

            /* indirect sale active point marketing date is according nota date */
            else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * add point active to marketing if it has
     * never been added
     *
     * @param [type] $is_order_active
     * @param [type] $sales_order_id
     * @return void
     */
    public function addPointActiveToMarketing($is_order_active, $order)
    {
        if ($is_order_active && empty($order->afftected_by_return) && !is_return_order_exist($order->store_id, $order->personel_id, confirmation_time($order)->format("Y"), confirmation_time($order)->quarter)) {

            $log_worker_point_marketing = $this->log_worker_point_marketing
                ->where("sales_order_id", $order->id)
                ->where("is_active", false)
                ->first();

            /* point active has never ben added */
            if ($log_worker_point_marketing) {
                $total_active_point_order = $this->sales_order_detail
                    ->where("sales_order_id", $order->id)
                    ->whereHas("sales_order", function ($QQQ) {
                        return $QQQ->whereHas("logWorkerPointMarketing", function ($QQQ) {
                            return $QQQ->where("is_active", false);
                        });
                    })
                    ->sum("marketing_point");
            } else {

                /* point active has been added to marketing */
                $total_active_point_order = 0;
            }

            /* add point to marketing */
            $point_marketing = $this->point_marketing->query()
                ->where('personel_id', $order->personel_id)
                ->where('year', confirmation_time($order)->format("Y"))
                ->first();

            if ($point_marketing) {
                $point_marketing->marketing_point_active += $total_active_point_order;
                $point_marketing->marketing_point_redeemable += $total_active_point_order;
                $point_marketing->save();
            } else {
                $point_marketing = $this->point_marketing->create([
                    "personel_id" => $order->personel_id,
                    "year" => confirmation_time($order)->format("Y"),
                    "marketing_point_total" => $total_active_point_order,
                    "marketing_point_active" => $total_active_point_order,
                    "marketing_point_adjustment" => 0,
                    "marketing_point_redeemable" => $total_active_point_order,
                    "status" => "not_redeemed",
                ]);
            }

            /* update log point */
            $log_worker_point_marketing = $this->log_worker_point_marketing
                ->where("sales_order_id", $order->id)
                ->where("is_count", true)
                ->update([
                    "is_active" => "1",
                ]);

            $log_worker_point_marketing_active = $this->log_worker_point_marketing_active->firstOrCreate([
                "sales_order_id" => $order->id,
            ]);

            return $point_marketing;
        }
    }
}
