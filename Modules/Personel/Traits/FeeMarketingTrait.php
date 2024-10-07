<?php

namespace Modules\Personel\Traits;

use App\Traits\DistributorStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\ClassHelper\PaymentTimeForFee;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Traits\SalesOrderTrait;

/**
 * Fee marketing recalculate
 */
trait FeeMarketingTrait
{
    use SalesOrderTrait;
    use DistributorStock;

    public function isFollowUp($sales_order, $follow_up_days_reference)
    {
        if ($sales_order->counter_id != null && $sales_order->follow_up_days > ($follow_up_days_reference ? $follow_up_days_reference->follow_up_days : 60)) {
            return true;
        }
        return false;
    }

    /**
     * only query to fee sharing
     */
    public function feeMarketingRegulerTotalQuery($personel_id, $year, $quarter, $sales_order = null)
    {
        $fee_sharing_origins = $this->fee_sharing_origin->query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->consideredOrder();
                },
            ])
            ->when($sales_order, function ($QQQ) use ($sales_order) {
                return $QQQ
                    ->where("sales_order_id", $sales_order->id)
                    ->whereDoesntHave("logMarketingFeeCounter", function ($QQQ) {
                        return $QQQ->where("type", "reguler");
                    });
            })
            ->where("personel_id", $personel_id)
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->where("is_checked", true)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ
                    ->consideredOrder()
                    ->isOffice(false);
            })
            ->whereHas("salesOrderDetail", function ($QQQ) use ($year, $quarter) {
                return $QQQ->orderDetailConsideredGetFee($year, $quarter, "1");
            })
            ->get();

        return $fee_sharing_origins;
    }

    /**
     * only query to fee sharing active
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @param [type] $sales_order
     * @return void
     */
    public function feeMarketingRegulerActiveQuery($personel_id, $year, $quarter, $sales_order = null)
    {
        $fee_sharing_origins = $this->fee_sharing_origin->query()
            ->with([
                "logMarketingFeeCounter" => function ($QQQ) {
                    return $QQQ
                        ->where("type", "reguler")
                        ->where("is_settle", true);
                },
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice",
                    ]);
                },
                "salesOrderOrigin" => function ($QQQ) {
                    return $QQQ->with([
                        "direct" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                    ]);
                },
            ])
            ->when($sales_order, function ($QQQ) use ($sales_order) {
                return $QQQ
                    ->where("sales_order_id", $sales_order->id)
                    ->whereDoesntHave("logMarketingFeeCounter", function ($QQQ) {
                        return $QQQ
                            ->where("type", "reguler")
                            ->where("is_settle", true);
                    });
            })
            ->where("personel_id", $personel_id)
            ->countedFeeAccordingOrigin()
            ->whereHas("salesOrder", function ($QQQ) use ($year, $quarter) {
                return $QQQ->feeMarketingActive($year, $quarter);
            })
            ->whereHas("salesOrderDetail", function ($QQQ) use ($year, $quarter) {
                return $QQQ->orderDetailConsideredGetFee($year, $quarter, "1");
            })
            ->where("is_returned", false)
            ->where("is_checked", true)
            ->get();

        return $fee_sharing_origins;
    }

    /**
     * fee reguler total
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeMarketingRegulerTotal($personel_id, $year, $quarter, $sales_order = null)
    {
        $fee_sharing_origins = $this->feeMarketingRegulerTotalQuery($personel_id, $year, $quarter, $sales_order = null);

        return $this->feeMarketingRegulerTotalDataMapping($fee_sharing_origins);
    }

    /**
     * fee reguler active
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeMarketingRegulerActive($personel_id, $year, $quarter, $sales_order = null, $status = "confirmed")
    {
        $fee_sharing_origins = $this->feeMarketingRegulerActiveQuery($personel_id, $year, $quarter, $sales_order = null);
        return $this->feeMarketingRegulerActiveDataMapping($fee_sharing_origins, $status);
    }

    /**
     * fee origin data mapping
     *
     * @param [type] $fee_sharings
     * @return void
     */
    public function feeMarketingRegulerTotalDataMapping($fee_sharings)
    {
        $fee_sharings = $fee_sharings
            ->each(function ($origin) {
                $log = LogMarketingFeeCounter::updateOrCreate([
                    "fee_sharing_origin_id" => $origin->id,
                    "sales_order_id" => $origin->salesOrder->id,
                    "type" => "reguler",
                ]);
            });

        $fee_sharing_doesthave_origin = $fee_sharings
            ->whereNull("sales_order_origin_id")
            ->groupBy("sales_order_detail_id");

        $fee_sharing_has_origin = $fee_sharings
            ->whereNotNull("sales_order_origin_id")
            ->groupBy("sales_order_origin_id");

        return $this->feeMarketingRegulerActiveOriginFilter($fee_sharing_has_origin) + $this->feeMarketingRegulerActiveOriginFilter($fee_sharing_doesthave_origin);
    }

    /**
     * fee origin data mapping
     *
     * @param [type] $fee_sharings
     * @return void
     */
    public function feeMarketingRegulerActiveDataMapping($fee_sharings, $status = "confirmed")
    {
        $fee_sharings = $fee_sharings
            ->groupBy("sales_order_id")

            /* pending and confirmed order will separeted */
            ->filter(fn($origin_per_order) => $origin_per_order[0]->salesOrder->status == $status)

            /**
         * reject if order was direct and settle beyond maturity,
         * and indirect sales considerd as settle
         */
            ->reject(fn($origin_per_order, $sales_order_id) => !$this->isSettleBeforeMaturity($origin_per_order[0]->salesOrder))
            ->flatten()
            ->groupBy("sales_order_origin_id")
            ->reject(function ($origin_so_origin, $sales_order_origin_id) {

                /**
             * indirect sale will counted as active if direct origin
             * from it indirect sale also counted as active
             * (rule: settle < maximum_settle_days)
             */
                if ($sales_order_origin_id) {

                    if ($origin_so_origin[0]->salesOrder->type == "2" && $origin_so_origin[0]->salesOrderOrigin) {
                        if ($origin_so_origin[0]->salesOrderOrigin->direct) {
                            if ($origin_so_origin[0]->salesOrderOrigin->direct->invoice->payment_status == "settle") {

                                /* payment time proforma according fee position maximum settle days */
                                if (PaymentTimeForFee::paymentTimeForFeeCalculation($origin_so_origin[0]->salesOrderOrigin?->direct?->invoice) > maximum_settle_days(confirmation_time($origin_so_origin[0]->salesOrderOrigin?->direct)->format("Y"))) {
                                    return $origin_so_origin;
                                }
                            } else if ($origin_so_origin[0]->salesOrderOrigin->direct->invoice->payment_status != "settle") {
                                return $origin_so_origin;
                            }
                        }
                    }

                }
            })
            ->flatten();

        $fee_sharing_has_origin = $fee_sharings
            ->whereNotNull("sales_order_origin_id")
            ->groupBy("sales_order_origin_id");

        $fee_sharing_doesthave_origin = $fee_sharings
            ->whereNull("sales_order_origin_id")
            ->groupBy("sales_order_detail_id");

        return $this->feeMarketingRegulerActiveOriginFilter($fee_sharing_has_origin) + $this->feeMarketingRegulerActiveOriginFilter($fee_sharing_doesthave_origin);
    }

    public function feeMarketingRegulerActiveOriginFilter($fee_sharings)
    {
        /* grouped fee sharing */
        return $fee_sharings
            ->map(function ($origin_per_order_detail, $sales_order_detail_id) {
                $origin_non_purchser = $origin_per_order_detail
                    ->where("fee_status", "!=", "purchaser")
                    ->where("handover_status", true)
                    ->first();

                if ($origin_non_purchser) {
                    $origin_per_order_detail
                        ->where("fee_status", "=", "purchaser")
                        ->map(function ($origin_purchser) {
                            $origin_purchser->fee_shared = 0;
                            return $origin_purchser;
                        });
                }

                return $origin_per_order_detail;
            })
            ->flatten()
            ->sum("fee_shared");
    }

    /**
     * fee sharing update personel_id
     *
     * @param [type] $personel
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeSharingRegulerSpesificMarketing($personel, $year, $quarter)
    {
        /**
         * follow up order will get fee if follow up days
         * more than 60 days
         */
        $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

        $personel_child = collect($this->getChildren($personel->id))->reject(fn($personel_id) => $personel_id == $personel->id)->toArray();

        $fee_sharing_origins = $this->fee_sharing_origin->query()
            ->with([
                "salesOrder.personel.supervisorInConfirmedOrder",
                "statusFee",
            ])
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->confirmedOrder();
            })
            ->countedFeeAccordingOrigin()
            ->where(function ($QQQ) use ($personel, $personel_child) {
                return $QQQ
                    ->where("personel_id", $personel->id)

                    /* as marketing and get no fee */
                    ->orWhere(function ($QQQ) use ($personel, $personel_child) {
                        return $QQQ
                            ->whereNull("personel_id")
                            ->whereHas("salesOrder", function ($QQQ) use ($personel, $personel_child) {
                                return $QQQ->where("personel_id", $personel->id);
                            });
                    })
                    /* as spv and get no fee */
                    ->orWhere(function ($QQQ) use ($personel, $personel_child) {
                        return $QQQ
                            ->whereNull("personel_id")
                            ->whereHas("salesOrder", function ($QQQ) use ($personel, $personel_child) {
                                return $QQQ
                                    ->whereIn("personel_id", $personel_child)
                                    ->where("personel_id", "!=", $personel->id);
                            });
                    });
            })
            ->get()
            ->groupBy("sales_order_id")
            ->flatten()
            ->map(function ($origin) use ($personel) {
                $origin->personel_updated = $personel;
                return $origin;
            });

        return $fee_sharing_origins;
    }

    /**
     * fee sharing origin data mapping
     *
     * @param [type] $fee_sharing_origins
     * @return void
     */
    public function feeSharingOriginDataMapping($fee_sharing_origins)
    {
        return $fee_sharing_origins
            ->map(function ($origin) use (&$marketing_buyer_join_date_days) {

                /**
             * if marketing as purchaser
             * exist
             */
                $personel_on_purchase = $origin->personel_updated;
                if (!empty($personel_on_purchase)) {
                    $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(Carbon::parse($origin->confirmed_at), false) : 0;
                }

                $spv_marketing_purchaser_in_order = null;
                if ($origin->salesOrder->personel_id == $origin->personel_updated->id) {
                    $spv_marketing_purchaser_in_order = $origin->personel_updated->id;
                } else {
                    $spv_marketing_purchaser_in_order = $origin->salesOrder->personel->supervisorInConfirmedOrder ? json_decode($origin->salesOrder->personel->supervisorInConfirmedOrder->properties)->attributes->supervisor_id : null;
                }

                $origin->spv_marketing_purchaser_in_order = $spv_marketing_purchaser_in_order;
                $origin->marketing_purchaser = $origin->salesOrder->personel_id;
                $origin->status_fee_name = $origin->statusFee->name;
                $origin->marketing_buyer_join_date_days = $marketing_buyer_join_date_days;

                $as_marketing = $origin->salesOrder->personel_id == $origin->personel_updated->id ? true : false;
                $origin->as_marketing = $as_marketing;

                /**
             * as marketing if join days less than 90 and status fee name is not R then this marketing
             * will not get fee for this origin, as spv if join days less then 90 no matter
             * what it's origin status fee name, then this marketing updated will not
             * get fee, important resign date must more then origin confirmed_at
             */
                $set_to_null = false;
                if (($marketing_buyer_join_date_days < 90 && $origin->status_fee_name != "R" && $as_marketing)
                    || (!empty($origin->personel_updated->resign_date) && $origin->personel_updated->resign_date > Carbon::parse($origin->confirmed_at)->format("Y-m-d"))
                    || ($marketing_buyer_join_date_days < 90 && !$as_marketing)
                ) {
                    $set_to_null = true;
                }

                /**
             * if join days more than 90 or status fee name is R and act as marketing then this marketing
             * will get fee for this origin, important resign date must more then origin confirmed_at
             */
                $set_to_get_fee = false;
                if (($marketing_buyer_join_date_days >= 90 || ($origin->status_fee_name == "R" && $as_marketing))
                    && ($origin->personel_updated->resign_date > Carbon::parse($origin->confirmed_at)->format("Y-m-d") || empty($origin->personel_updated->resign_date))
                ) {
                    $set_to_get_fee = true;
                }

                $origin->set_to_null = $set_to_null;
                $origin->set_to_get_fee = $set_to_get_fee;

                $origin->unsetRelation("salesOrder");
                $origin->unsetRelation("statusFee");
                return $origin;
            })
            ->reject(function ($origin) use ($marketing_buyer_join_date_days) {
                return $origin->personel_id != $origin->spv_marketing_purchaser_in_order && $origin->personel_id != null;
            })
            ->map(function ($origin) {
                $personel_id = null;
                if ($origin->set_to_null) {
                    $origin->personel_id = null;
                } else {
                    $personel_id = $origin->personel_id;
                }

                if ($origin->set_to_get_fee) {
                    $origin->personel_id = $origin->personel_updated->id;
                    $personel_id = $origin->personel_updated->id;;
                }

                $update = FeeSharingSoOrigin::query()
                    ->where("id", $origin->id)
                    ->update([
                        "personel_id" => $personel_id,
                    ]);

                return collect($origin)->forget("personel_updated");
            });
    }

    /**
     * recalculate fee marketing oper product
     *
     * @return void
     */
    public function feeMarketingPerProductCalculator($sales_order)
    {
        /**
         * distributor check, distributor does not
         * get fee at all. Only distributor
         * which has contrack active
         */
        if ($sales_order->dealer) {

            if (collect($sales_order->dealer->ditributorContract)->count() > 0) {
                $active_contract = $this->distributorActiveContract($sales_order->store_id, confirmation_time($sales_order)->format("Y-m-d"));

                if ($active_contract) {

                    $sales_order_detail = $this->sales_order_detail
                        ->where([
                            "sales_order_id" => $sales_order->id,
                        ])
                        ->update([
                            "marketing_fee" => 0,
                            "marketing_fee_reguler" => 0,
                        ]);

                    $log = $this->log_worker_sales_fee->firstOrCreate([
                        "sales_order_id" => $sales_order->id,
                    ], [
                        "type" => $sales_order->type,
                        "checked_at" => now(),
                    ]);

                    return "distributor contract";
                }
            }
        }

        if (collect($sales_order->salesOrderDetail)->count() > 0) {

            collect($sales_order->salesOrderDetail)->each(function ($detail) use ($sales_order) {
                $quantity = $detail->quantity - $detail->returned_quantity;
                $fee = 0;
                $fee_reguler = 0;

                $fee_product_reguler_according_order_year = DB::table('fee_products')
                    ->whereNull("deleted_at")
                    ->where("product_id", $detail->product_id)
                    ->where("year", confirmation_time($sales_order)->format("Y"))
                    ->where("quartal", confirmation_time($sales_order)->quarter)
                    ->where("type", "1")
                    ->first();

                if (!empty($fee_product_reguler_according_order_year)) {
                    $fee_reguler = $fee_product_reguler_according_order_year->fee * $quantity;

                    $sales_order_detail = $this->sales_order_detail
                        ->where("id", $detail->id)
                        ->update(["marketing_fee_reguler" => $fee_reguler]);

                    /**
                     * update fe origin if exist, its may be cause
                     * the quantity wa change, then origin need
                     * to update
                     */
                    $this->fee_sharing_origin->query()
                        ->where("sales_order_detail_id", $detail->id)
                        ->get()
                        ->each(function ($origin) use ($fee_reguler) {
                            $origin->total_fee = $fee_reguler;
                            if ($fee_reguler <= 0) {
                                $origin->fee_shared = 0;
                            }
                            $origin->save();
                        });
                }
            });

            /**
             * fee sharing percentaion nominal also need to be update
             * if there any update of quantity product
             */
            $fee_sharing_origin = $this->fee_sharing_origin->query()
                ->where("sales_order_id", $sales_order->id)
                ->first();

            if ($fee_sharing_origin) {
                $this->feeSharingOriginCalculator($sales_order);
            }
        }

        $log = $this->log_worker_sales_fee->firstOrCreate([
            "sales_order_id" => $sales_order->id,
        ], [
            "type" => $sales_order->type,
            "checked_at" => now(),
        ]);

        return "fee per product checked";
    }

    /**
     * pending
     *
     * @return void
     */
    public function feeSharingOriginGenerator($sales_order, $delete_origin = true)
    {

        /**
         * delete all fee sharing origin from this order
         */
        if ($delete_origin) {
            $this->fee_sharing_origin->query()
                ->where("sales_order_id", $sales_order->id)
                ->delete();
        }

        $active_contract = $this->distributorActiveContract($sales_order->store_id, confirmation_time($sales_order)->format("Y-m-d"));
        if ($active_contract) {
            return "sales to distributor active";
        } elseif (!$sales_order->personel_id) {
            return "sales order with empty marketing";
        } elseif ($sales_order->is_office) {
            return "sales order considered offfice";
        }

        /**
         * return order or affected from retrun still get fee
         * and calculate for fee reguler total only
         */

        /**
         * fee is for marketing on purchase
         */
        $personel_on_purchase = $this->personel
            ->with([
                "position" => function ($QQQ) {
                    return $QQQ->with("fee");
                },
            ])
            ->where("id", $sales_order->personel_id)
            ->first();

        /* get marketing with all his supervisor to top supervisor */
        $marketing_supervisor = $this->parentPersonel($sales_order->personel_id, confirmation_time($sales_order) ? confirmation_time($sales_order)->format("Y-m-d") : $sales_order->created_at->format("Y-m-d"));

        $marketing_list = $this->personel->query()
            ->with("position.fee")
            ->whereNull("deleted_at")
            ->whereIn("id", $marketing_supervisor)
            ->get();

        $marketing_list = collect($marketing_list)->reject(fn($marketing) => $marketing->resign_date ? $marketing->resign_date > confirmation_time($sales_order)->format("Y-m-d") : null);

        $all_spv = $marketing_list;

        /* get status fee hand over status */
        $status_fee = $sales_order->statusFee;

        /* fee position list excludeing purchaser marketing */
        $fee_sharing_data_references = $this->fee_position
            ->with("position")
            ->whereHas("position", function ($QQQ) {
                return $QQQ;
            })
            ->orderBy("fee")
            ->get();

        /* rm fee percentage */
        $rm_fee_percentage = $fee_sharing_data_references
            ->where("fee_as_marketing", true)
            ->first();

        $marketing_get_fee = collect([]);

        /**
         * marketing fee cut according follow up
         */
        $marketing_buyer_join_date_days = 0;

        /**
         * if marketing as purchaser
         * exist
         */
        if (!empty($personel_on_purchase)) {
            if ($personel_on_purchase->join_date) {
                $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays((confirmation_time($sales_order)), false) : 0;
            }
        }

        /**
         * follow up order will get fee if follow up days
         * more than 60 days
         */
        $follow_up_days_reference = DB::table('fee_follow_ups')
            ->whereNull("deleted_at")
            ->orderBy("follow_up_days")
            ->first();

        /**
         * fee cut according sales counter
         */
        $is_fee_cut_for_sc = false;
        $sc_reduction_percentage = 0;

        /* sc fee reduction percentage */
        $sc_fee_reduction = DB::table('fee_follow_ups')
            ->whereNull("deleted_at")
            ->orderBy("follow_up_days")
            ->get();

        $sc_fee_reduction->each(function ($fee_sc) use (&$sc_reduction_percentage, $sales_order) {
            if ($sales_order->follow_up_days > $fee_sc->follow_up_days) {
                $sc_reduction_percentage = $fee_sc->fee;
            }
        });

        /**
         * generete fee sharing per product
         */
        collect($sales_order->salesOrderDetail)->each(function ($order_detail) use (
            $marketing_buyer_join_date_days,
            $fee_sharing_data_references,
            $follow_up_days_reference,
            $sc_reduction_percentage,
            $personel_on_purchase,
            &$marketing_get_fee,
            &$is_fee_cut_for_sc,
            $rm_fee_percentage,
            $marketing_list,
            $sales_order,
            $status_fee,
        ) {

            /**
             * if sales order is from follow up and follow days more than 60 days
             * fee marketing is cut according
             * to the fee on status fee
             * data reference
             */
            if ($sales_order->counter_id != null && $sales_order->follow_up_days > ($follow_up_days_reference ? $follow_up_days_reference->follow_up_days : 60)) {

                /* order mark as folow up, fee will cut for sc */
                $is_fee_cut_for_sc = true;

                if ($order_detail->salesOrderOrigin) {
                    collect($order_detail->allSalesOrderOrigin)->each(function ($origin) use (
                        $marketing_buyer_join_date_days,
                        $fee_sharing_data_references,
                        $follow_up_days_reference,
                        $sc_reduction_percentage,
                        $personel_on_purchase,
                        &$marketing_get_fee,
                        &$is_fee_cut_for_sc,
                        $rm_fee_percentage,
                        $marketing_list,
                        $order_detail,
                        $sales_order,
                        $status_fee,
                    ) {

                        /**
                         * sales counter fee
                         */
                        $marketing_get_fee->push(collect([
                            "personel_id" => $sales_order ? $sales_order->counter_id : null,
                            "position_id" => $sales_order->salesCounter->position_id,
                            "sales_order_origin_id" => $origin->id,
                            "sales_order_id" => $sales_order->id,
                            "sales_order_detail_id" => $order_detail->id,
                            "fee_percentage" => $sc_reduction_percentage,
                            "status_fee" => $status_fee->id,
                            "handover_status" => 0,
                            "fee_status" => "sales counter",
                            "marketing_join_days" => 0,
                            "confirmed_at" => confirmation_time($sales_order),
                            "sc_reduction_percentage" => 0,
                        ]));

                    });
                } else {

                    /**
                     * sales counter fee
                     */
                    $marketing_get_fee->push(collect([
                        "personel_id" => $sales_order ? $sales_order->counter_id : null,
                        "position_id" => $sales_order->salesCounter->position_id,
                        "sales_order_origin_id" => null,
                        "sales_order_id" => $sales_order->id,
                        "sales_order_detail_id" => $order_detail->id,
                        "fee_percentage" => $sc_reduction_percentage,
                        "status_fee" => $status_fee->id,
                        "handover_status" => 0,
                        "fee_status" => "sales counter",
                        "marketing_join_days" => 0,
                        "confirmed_at" => confirmation_time($sales_order),
                        "sc_reduction_percentage" => 0,
                    ]));
                }
            }

            /**
             * marketing fee if order is from
             * handover store
             */
            if ($marketing_buyer_join_date_days > 90 && $status_fee->name != "R") {

                if ($order_detail->salesOrderOrigin) {
                    collect($order_detail->allSalesOrderOrigin)->each(function ($origin) use (
                        $marketing_buyer_join_date_days,
                        $fee_sharing_data_references,
                        $follow_up_days_reference,
                        $sc_reduction_percentage,
                        $personel_on_purchase,
                        &$marketing_get_fee,
                        &$is_fee_cut_for_sc,
                        $rm_fee_percentage,
                        $marketing_list,
                        $order_detail,
                        $sales_order,
                        $status_fee,
                    ) {
                        $marketing_get_fee->push(collect([
                            "personel_id" => $sales_order ? $sales_order->personel_id : null,
                            "position_id" => $rm_fee_percentage->position_id,
                            "sales_order_origin_id" => $origin->id,
                            "sales_order_id" => $sales_order->id,
                            "sales_order_detail_id" => $order_detail->id,
                            "fee_percentage" => $status_fee->percentage,
                            "status_fee" => $status_fee->id,
                            "handover_status" => 1,
                            "fee_status" => "marketing fee hand over",
                            "marketing_join_days" => $marketing_buyer_join_date_days,
                            "confirmed_at" => confirmation_time($sales_order),
                            "sc_reduction_percentage" => 0,
                        ]));

                    });
                } else {
                    $marketing_get_fee->push(collect([
                        "personel_id" => $sales_order ? $sales_order->personel_id : null,
                        "position_id" => $rm_fee_percentage->position_id,
                        "sales_order_origin_id" => null,
                        "sales_order_id" => $sales_order->id,
                        "sales_order_detail_id" => $order_detail->id,
                        "fee_percentage" => $status_fee->percentage,
                        "status_fee" => $status_fee->id,
                        "handover_status" => 1,
                        "fee_status" => "marketing fee hand over",
                        "marketing_join_days" => $marketing_buyer_join_date_days,
                        "confirmed_at" => confirmation_time($sales_order),
                        "sc_reduction_percentage" => 0,
                    ]));
                }
            }

            /**
             * all marketing position saved even
             * though marketing is not exist,
             * and spv get double fee as
             * purchaser
             */
            collect($fee_sharing_data_references)->map(function ($fee, $key) use (
                $sc_reduction_percentage,
                $personel_on_purchase,
                $marketing_get_fee,
                $is_fee_cut_for_sc,
                $marketing_list,
                $order_detail,
                $sales_order,
                $status_fee,
            ) {
                $personel_id = collect($marketing_list)
                    ->filter(function ($marketing, $key) use ($fee, $sales_order) {
                        return marketing_position_according_date($marketing, confirmation_time($sales_order)) == $fee->position_id;
                    })
                    ->first();

                /* personel purchaser */
                $fee_status = null;
                if ($fee->fee_as_marketing == 1) {
                    $personel_id = $personel_on_purchase;
                    $fee_status = "purchaser";
                }

                /* check join date */
                $marketing_join_days = $personel_id ? ($personel_id->join_date ? Carbon::parse($personel_id->join_date)->diffInDays(confirmation_time($sales_order), false) : 0) : 0;

                /**
                 * purchaser did not get fee if join date < 91
                 * even though the store is reguler
                 */
                if ($marketing_join_days < 91 || !$personel_id) {
                    $personel_id = null;
                }

                /* applicator fee */
                if ($fee->is_applicator != null) {
                    $fee_status = "applicator";
                } else if ($fee->is_mm != null) {
                    $fee_status = "marketing manager";
                }

                if ($order_detail->salesOrderOrigin) {

                    collect($order_detail->allSalesOrderOrigin)->each(function ($origin) use (
                        $sc_reduction_percentage,
                        $personel_on_purchase,
                        $marketing_join_days,
                        $marketing_get_fee,
                        $is_fee_cut_for_sc,
                        $marketing_list,
                        $order_detail,
                        $sales_order,
                        $personel_id,
                        $status_fee,
                        $fee_status,
                        $fee,
                    ) {
                        $marketing_get_fee->push(collect([
                            "personel_id" => $personel_id ? $personel_id->id : null,
                            "position_id" => $fee->position_id,
                            "sales_order_origin_id" => $origin->id,
                            "sales_order_id" => $sales_order->id,
                            "sales_order_detail_id" => $order_detail->id,
                            "fee_percentage" => $fee->fee,
                            "status_fee" => $status_fee->id,
                            "status_fee_name" => $status_fee->name,
                            "handover_status" => 0,
                            "fee_status" => $fee_status,
                            "marketing_join_days" => $marketing_join_days,
                            "confirmed_at" => confirmation_time($sales_order),
                            "sc_reduction_percentage" => $is_fee_cut_for_sc ? ($fee->follow_up ? $sc_reduction_percentage : 0) : 0,
                        ]));

                    });
                } else {
                    $marketing_get_fee->push(collect([
                        "personel_id" => $personel_id ? $personel_id->id : null,
                        "position_id" => $fee->position_id,
                        "sales_order_origin_id" => null,
                        "sales_order_id" => $sales_order->id,
                        "sales_order_detail_id" => $order_detail->id,
                        "fee_percentage" => $fee->fee,
                        "status_fee" => $status_fee->id,
                        "status_fee_name" => $status_fee->name,
                        "handover_status" => 0,
                        "fee_status" => $fee_status,
                        "marketing_join_days" => $marketing_join_days,
                        "confirmed_at" => confirmation_time($sales_order),
                        "sc_reduction_percentage" => $is_fee_cut_for_sc ? ($fee->follow_up ? $sc_reduction_percentage : 0) : 0,
                    ]));
                }
            });
        });

        /* store data fee sharing */
        foreach ($marketing_get_fee->toArray() as $marketing) {
            $marketing = (object) $marketing;

            $log = $this->fee_sharing_origin->create([
                "personel_id" => $marketing->personel_id,
                "position_id" => $marketing->position_id,
                "sales_order_detail_id" => $marketing->sales_order_detail_id,
                "sales_order_id" => $marketing->sales_order_id,
                "fee_status" => $marketing->fee_status,
                "fee_percentage" => $marketing->fee_percentage,
                "sales_order_origin_id" => $marketing->sales_order_origin_id,
                "handover_status" => $marketing->handover_status,
                "status_fee" => $marketing->status_fee,
                "confirmed_at" => $marketing->confirmed_at,
                "sc_reduction_percentage" => $marketing->sc_reduction_percentage,
            ]);

        }

        return "fee sharing generated, total: " . $marketing_get_fee->count();
    }

    /**
     * populate fee to marketing and supervisor
     *
     * @param [type] $sales_order
     * @return void
     */
    public function feeSharingOriginCalculator($sales_order)
    {
        $fee_sharing_origins = $this->fee_sharing_origin
            ->with([
                "salesOrderOrigin",
                "salesOrderDetail",
                "feePosition",
            ])
            ->whereHas("salesOrderDetail")
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->consideredOrder();
            })
            ->where("sales_order_id", $sales_order->id)
            ->get();

        $marketing_fee = collect();

        /* add quantity */
        $fee_sharing_origins = $fee_sharing_origins
            ->map(function ($origin) {
                $origin["quantity"] = $origin->salesOrderDetail->quantity - $origin->salesOrderDetail->returned_quantity;
                if ($origin->salesOrderOrigin) {
                    $origin->quantity = $origin->salesOrderOrigin->quantity_from_origin;
                }
                return $origin;
            });

        $fee_product = null;

        /* fee sharing has so origin */
        $grouped_origin = $fee_sharing_origins
            ->whereNotNull("sales_order_origin_id")
            ->groupBy("sales_order_origin_id");

        $this->feeSharingOriginCalculatorDataMapper($grouped_origin, "sales_order_origin_id", $marketing_fee);

        /* fee sharing doent have origin */
        $grouped_origin = $fee_sharing_origins
            ->whereNull("sales_order_origin_id")
            ->groupBy("sales_order_detail_id");

        return $this->feeSharingOriginCalculatorDataMapper($grouped_origin, "sales_order_detail_id", $marketing_fee);
    }

    public function feeSharingOriginCalculatorDataMapper($fee_sharing_origin_grouped, $group_by, $marketing_fee)
    {
        $fee_sharing_origin_grouped
            ->each(function ($origin_per_so_origin, $group_by_id) use (&$marketing_fee, &$fee_product, $group_by) {
                $product_id = $origin_per_so_origin->first()->salesOrderDetail->product_id;

                $quantity = $origin_per_so_origin->first()->quantity - $origin_per_so_origin->first()->returned_quantity;
                $fee = 0;

                /* get fee reguler data reference */
                $fee_product = DB::table('fee_products')
                    ->whereNull("deleted_at")
                    ->where("product_id", $product_id)
                    ->where('type', 1)
                    ->where("year", Carbon::parse($origin_per_so_origin->first()->confirmed_at)->format("Y"))
                    ->where("quartal", Carbon::parse($origin_per_so_origin->first()->confirmed_at)->quarter)
                    ->first();

                if (!$fee_product) {
                    /* there has no fee product found */

                    /* if there have no fee target */
                    $fee_sharing_origin = $this->fee_sharing_origin
                        ->where($group_by, $group_by_id)
                        ->update([
                            "is_checked" => "1",
                        ]);

                    /* retrun true for continue */
                    return true;
                }

                $fee_reguler = $quantity * $fee_product->fee;

                /**
             * |-------------------------------------------------------------------------
             * | fee sharing distribution to marketing according fee sharing origin percentage
             * |-------------------------------------------------------------------------
             */

                /* check status fee */
                $is_handover = $origin_per_so_origin
                    ->filter(function ($fee, $key) use ($fee_reguler) {
                        return $fee->handover_status == 1;
                    })
                    ->first();

                /**
             *
             * fill fee reguler shared and
             * fee target shared
             */
                $sales_counter = $origin_per_so_origin
                    ->filter(function ($fee, $key) {
                        $fee->unsetRelation("salesOrder");
                        return $fee->fee_status == "sales counter";
                    })
                    ->first();

                /**
             *
             * fee sharing purchaser
             */
                $fee_purchaser = $origin_per_so_origin
                    ->filter(function ($fee, $key) {
                        $fee->unsetRelation("salesOrder");
                        return $fee->fee_status == "purchaser";
                    })
                    ->first();

                $origin_per_so_origin->map(function ($fee, $key) use (
                    &$marketing_fee,
                    $sales_counter,
                    $fee_purchaser,
                    $fee_reguler,
                    $is_handover,
                    $fee_product,
                    $product_id,
                    $quantity,
                ) {
                    $marketing_fee_reguler = 0;
                    $marketing_fee_reguler_before_cut = $fee_reguler * $fee->fee_percentage / 100;

                    /* sales counter fee check */
                    if ($sales_counter) {

                        /**
                     * fee will be cut for sales counter fom follow up with that rules
                     * only reguler fee cuts on follow up
                     * target fee does not change
                     */

                        /* store is handover from another marketing  */

                        if ($fee->fee_status == "marketing fee hand over") {
                            $marketing_fee_reguler_before_cut = (($fee_reguler * $fee_purchaser->fee_percentage / 100) - ($fee_purchaser->sc_reduction_percentage > 0 ? (($fee_reguler * $fee_purchaser->fee_percentage / 100) * ($fee_purchaser->sc_reduction_percentage / 100)) : 0));
                            $marketing_fee_reguler = (($fee_reguler * $fee_purchaser->fee_percentage / 100) - ($fee_purchaser->sc_reduction_percentage > 0 ? (($fee_reguler * $fee_purchaser->fee_percentage / 100) * ($fee_purchaser->sc_reduction_percentage / 100)) : 0)) * $fee->fee_percentage / 100;
                        } else {
                            $marketing_fee_reguler = ($fee_reguler * $fee->fee_percentage / 100) - ($fee->sc_reduction_percentage > 0 ? (($fee_reguler * $fee->fee_percentage / 100) * ($fee->sc_reduction_percentage / 100)) : 0);
                        }
                    } else {

                        /* store is handover from another marketing  */
                        if ($fee->fee_status == "marketing fee hand over") {
                            $marketing_fee_reguler = ($fee_reguler * $fee_purchaser->fee_percentage / 100) * $fee->fee_percentage / 100;
                            $marketing_fee_reguler_before_cut = $fee_reguler * $fee_purchaser->fee_percentage / 100;
                        } else {
                            $marketing_fee_reguler = $fee_reguler * $fee->fee_percentage / 100;
                        }
                    }

                    $marketing_fee->push(collect([
                        "id" => $fee->id,
                        "personel_id" => $fee->personel_id,
                        "position_id" => $fee->position_id,
                        "fee_percentage" => $fee->fee_percentage,
                        "fee_shared_before_cut" => $marketing_fee_reguler_before_cut,
                        "fee_shared" => $marketing_fee_reguler,
                        "sc_reduction_percentage" => $fee->sc_reduction_percentage,
                        "fee_status" => $fee->fee_status,
                        "sales_order_origin_id" => $fee->sales_order_origin_id,
                        "sales_order_id" => $fee->sales_order_id,
                        "sales_order_detail_id" => $fee->sales_order_detail_id,
                        "total_fee" => $fee_reguler,
                        "quantity" => $quantity,
                        "fee" => $fee_product->fee,
                        "product_id" => $product_id,
                    ]));
                    $fee->unsetRelation("salesOrder");
                });

                /**
             * purchaser fee normal
             */
                $marketing_fee_per_detail = $marketing_fee
                    ->sortBy("fee_percentage")
                    ->where($group_by, $group_by_id)
                    ->values();

                $fee_purchaser = $marketing_fee_per_detail
                    ->filter(function ($fee, $key) {
                        return $fee["fee_status"] == "purchaser";
                    })
                    ->first();

                /* fee sales counter */
                if ($sales_counter) {
                    $fee_position_cut_for_sc = $this->fee_position->query()
                        ->get()
                        ->where("follow_up", "1")
                        ->pluck("position_id")
                        ->toArray();

                    $fee_sales_counter = $marketing_fee_per_detail
                        ->filter(function ($fee, $key) use ($fee_position_cut_for_sc, $is_handover) {
                            return in_array($fee["position_id"], $fee_position_cut_for_sc) && $fee["sc_reduction_percentage"] > 0;
                        })
                        ->sum("fee_shared");

                    /**
                 * update fee sharing origin for sales counter
                 */
                    $marketing_fee_per_detail = $marketing_fee_per_detail
                        ->map(function ($fee, $key) use ($fee_sales_counter, $fee_reguler) {
                            if ($fee["fee_status"] == "sales counter") {
                                $fee["fee_shared"] = $fee_sales_counter;
                            }
                            return $fee;
                        });
                }

                $marketing_fee_per_detail->each(function ($fee) {

                    /* update fee shairing */
                    $fee_sharing_update = $this->fee_sharing_origin->query()
                        ->where("id", $fee["id"])
                        ->update([
                            "fee_shared" => $fee["fee_shared"],
                            "is_checked" => 1,
                            "total_fee" => $fee["total_fee"],
                        ]);
                });

            });

        return "fee sharing calculated";
    }

    /*
    |-----------------------------------------
    | FEE TARGET
    |---------------------------------
    |
    |
    |
     * fee target marketing total
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeMarketingTargetTotal($personel_id, $year, $quarter)
    {
        $fee_target_sharings = FeeTargetSharing::query()
            ->where("year", $year)
            ->where("quarter", $quarter)
            ->get()
            ->sum("fee_shared");

        // FeeTarget
        // $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
        //     ->with([
        //         "feeProduct" => function ($QQQ) use ($year) {
        //             return $QQQ
        //                 ->where("year", $year)
        //                 ->where("type", "2");
        //         },
        //         "salesOrder",
        //     ])
        //     ->where("personel_id", $personel_id)
        //     ->countedFeeAccordingOrigin()
        //     ->whereYear("confirmed_at", $year)
        //     ->whereRaw("quarter(confirmed_at) = ?", $quarter)
        //     ->whereHas("salesOrder", function ($QQQ) {
        //         return $QQQ->confirmedOrder();
        //     })
        //     ->where("is_returned", false)
        //     ->get()
        //     ->groupBy("sales_order_id")
        //     ->reject(function ($origin) {
        //         if ($origin[0]->salesOrder->counter_id) {
        //             return $origin;
        //         }
        //     })
        //     ->flatten()
        //     ->groupBy([
        //         function ($val) {return $val->personel_id;},
        //         function ($val) {return $val->position_id;},
        //         function ($val) {return $val->product_id;},
        //         function ($val) {return $val->status_fee_id;},
        //     ])

        // /* point total */
        //     ->map(function ($origin_per_marketing, $personel_id) use ($year) {
        //         $origin_per_marketing = $origin_per_marketing->map(function ($origin_per_fee_percentage, $position_id) use ($personel_id, $year) {
        //             $origin_per_fee_percentage = $origin_per_fee_percentage->map(function ($fee_per_product, $product_id) use ($position_id, $personel_id, $year) {
        //                 $fee_per_product = $fee_per_product->map(function ($origin_per_fee_status, $status_fee_id) use ($product_id, $position_id, $personel_id, $year) {

        //                     $fee_product = $origin_per_fee_status[0]->feeProduct->where("year", $year)->where("quantity", "<=", collect($origin_per_fee_status)->sum("quantity_unit"))->sortByDesc("quantity")->first();
        //                     $status_fee_percentage = $origin_per_fee_status[0]->status_fee_percentage;

        //                     $detail["personel_id"] = $personel_id;
        //                     $detail["product_id"] = $product_id;
        //                     $detail["fee_percentage"] = $origin_per_fee_status[0]->fee_percentage;
        //                     $detail["status_fee_percentage"] = $status_fee_percentage;
        //                     $detail["quantity"] = collect($origin_per_fee_status)->sum("quantity_unit");
        //                     $detail["fee_target"] = $fee_product ? $fee_product->fee : 0.00;
        //                     $detail["fee_target_nominal_before_cut"] = $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") : 0.00;
        //                     $detail["fee_target_nominal"] = $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") * $status_fee_percentage / 100 * $origin_per_fee_status[0]->fee_percentage / 100 : 0.00;

        //                     return $detail;
        //                 });

        //                 return $fee_per_product->values();
        //             });

        //             return $origin_per_fee_percentage->values()->flatten(1);
        //         });

        //         return $origin_per_marketing->values()->flatten(1);
        //     })
        //     ->flatten(1)
        //     ->sum("fee_target_nominal");

        // return $fee_target_sharing_origin;
    }

    /**
     * fee target marketing active
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeMarketingTargetActive($personel_id, $year, $quarter, $status = "confirmed")
    {
        /**
         * follow up order will get fee if follow up days
         * more than 60 days
         */
        $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

        $fee_target_sharing_origin_active = $this->fee_target_sharing_origin->query()
            ->with([
                "feeProduct" => function ($QQQ) use ($year) {
                    return $QQQ
                        ->where("year", $year)
                        ->where("type", "2");
                },
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice",
                        "lastReceivingGoodIndirect",
                    ]);
                },
                "salesOrderOrigin" => function ($QQQ) {
                    return $QQQ->with([
                        "direct" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                    ]);
                },
            ])
            ->where("personel_id", $personel_id)
            ->where("is_returned", false)
            ->countedFeeAccordingOrigin()
            ->whereHas("salesOrder", function ($QQQ) use ($year, $quarter) {
                return $QQQ->feeMarketingActive($year, $quarter);
            })
            ->get()
            ->groupBy("sales_order_id")

        /* pending and confirmed order will separeted */
            ->filter(fn($origin_per_order) => $origin_per_order[0]->salesOrder->status == $status)

        /* order from follow up is not count for fee */
            ->reject(function ($origin) {
                if ($origin[0]->salesOrder->counter_id) {
                    return $origin;
                }
            })

        /* reject if order was direct and settle beyond maturity */
            ->reject(fn($origin_per_order, $sales_order_id) => !$this->isSettleBeforeMaturity($origin_per_order[0]->salesOrder))

            ->flatten()
            ->groupBy("sales_order_detail_id")
            ->reject(function ($origin) {

                /**
             * indirect sale will counted as acive if direct origin
             * from it indirect sale also counted as active
             * (rule: settle < 93 days)
             */
                if ($origin[0]->salesOrder->type == "2" && $origin[0]->salesOrderOrigin?->direct?->invoice?->payment_status == "settle") {
                    if (PaymentTimeForFee::paymentTimeForFeeCalculation($origin[0]->salesOrderOrigin?->direct?->invoice) > maximum_settle_days(confirmation_time($origin[0]->salesOrderOrigin?->direct)->format("Y"))) {
                        return $origin;
                    }
                } elseif ($origin[0]->salesOrder->type == "2" && $origin[0]->salesOrderOrigin?->direct?->invoice?->payment_status != "settle") {
                    return $origin;
                }
            })
            ->flatten()
            ->groupBy([
                function ($val) {return $val->personel_id;},
                function ($val) {return $val->position_id;},
                function ($val) {return $val->product_id;},
                function ($val) {return $val->status_fee_id;},
            ])

        /* point total */
            ->map(function ($origin_per_marketing, $personel_id) use ($year) {
                $origin_per_marketing = $origin_per_marketing->map(function ($origin_per_fee_percentage, $position_id) use ($personel_id, $year) {
                    $origin_per_fee_percentage = $origin_per_fee_percentage->map(function ($fee_per_product, $product_id) use ($position_id, $personel_id, $year) {
                        $fee_per_product = $fee_per_product->map(function ($origin_per_fee_status, $status_fee_id) use ($product_id, $position_id, $personel_id, $year) {

                            $fee_product = $origin_per_fee_status[0]->feeProduct->where("year", $year)->where("quantity", "<=", collect($origin_per_fee_status)->sum("quantity_unit"))->sortByDesc("quantity")->first();
                            $status_fee_percentage = $origin_per_fee_status[0]->status_fee_percentage;
                            $detail["personel_id"] = $personel_id;
                            $detail["product_id"] = $product_id;
                            $detail["fee_percentage"] = $origin_per_fee_status[0]->fee_percentage;
                            $detail["status_fee_percentage"] = $status_fee_percentage;
                            $detail["quantity"] = collect($origin_per_fee_status)->sum("quantity_unit");
                            $detail["fee_target"] = $fee_product ? $fee_product->fee : 0.00;
                            $detail["fee_target_nominal_before_cut"] = $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") : 0.00;
                            $detail["fee_target_nominal"] = $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") * $status_fee_percentage / 100 * $origin_per_fee_status[0]->fee_percentage / 100 : 0.00;

                            return $detail;
                        });

                        return $fee_per_product->values();
                    });

                    return $origin_per_fee_percentage->values()->flatten(1);
                });

                return $origin_per_marketing->values()->flatten(1);
            })
            ->flatten(1)
            ->sum("fee_target_nominal");

        return $fee_target_sharing_origin_active;
    }

    /**
     * fee target sharing origin generator
     *
     * @param [type] $sales_order
     * @return void
     */
    public function feeTargetSharingOriginGenerator($sales_order, $delete_origin = true)
    {
        /**
         * delete all fee sharing origin from this order
         */
        if ($delete_origin) {
            $this->fee_target_sharing_origin->query()
                ->where("sales_order_id", $sales_order->id)
                ->get()
                ->each(function ($origin) {
                    $origin->delete();
                });
        }

        if ($sales_order->is_office) {
            return "sales order considered offfice";
        }

        /**
         * order must not from follow up,
         * no matter follow up days is
         * under 60 or not, and return
         * also count as total fee
         */
        if (empty($sales_order->counter_id)) {

            /* store was distributor when order */
            $active_contract = $this->distributorActiveContract($sales_order->store_id, confirmation_time($sales_order)->format("Y-m-d"));
            if ($active_contract) {
                return "fee target sharing not generated, from distributor";
            } else if (empty($sales_order->personel_id)) {
                return "sales order with empty marketing";
            }

            /**
             * fee sharing quantity product is shared to all marketing
             * includes supervisor, no matter marketing purchaser
             * is match to get fee or not, this action need to
             * check supervisor achievement
             */
            $personel_on_purchase = $this->personel->query()
                ->where("id", $sales_order->personel_id)
                ->withTrashed()
                ->first();

            /* get status fee hand over status */
            $status_fee = $sales_order->statusFee;

            /* get marketing with all his supervisor to top supervisor */
            $marketing_supervisor = $this->parentPersonel($sales_order->personel_id, confirmation_time($sales_order) ? confirmation_time($sales_order)->format("Y-m-d") : $sales_order->created_at->format("Y-m-d"));

            /* filter supervisor active and Haven't resigned yet */
            $marketing_list = $this->personel
                ->whereIn("id", $marketing_supervisor)
                ->get()
                ->reject(function ($marketing) use ($sales_order) {

                    /* the day marketing has resign still considered to get fee */
                    return $marketing->resign_date ? $marketing->resign_date > confirmation_time($sales_order)->format("Y-m-d") : null;
                });

            /* marketing join date check */
            $marketing_buyer_join_date_days = 0;
            if (!empty($personel_on_purchase)) {
                $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(confirmation_time($sales_order)->format("Y-m-d"), false) : 0;
            }

            /* get store hand over status */
            $store = null;
            if ($sales_order->dealer !== null) {
                $store = $sales_order->dealer;
            } else {
                $store = $sales_order->subDealer;
            }

            /* fee position list excludeing purchaser marketing */
            $fee_sharing_data_references = $this->fee_position
                ->with("position")
                ->whereHas("position", function ($QQQ) {
                    return $QQQ;
                })
                ->orderBy("fee")
                ->get();

            /* rm fee percentage */
            $rm_fee_percentage = $fee_sharing_data_references
                ->where("fee_as_marketing", true)
                ->first();

            $marketing_get_fee_target = collect();
            $sales_order->salesOrderDetail->each(function ($order_detail) use (
                $marketing_buyer_join_date_days,
                $fee_sharing_data_references,
                &$marketing_get_fee_target,
                $personel_on_purchase,
                $rm_fee_percentage,
                $marketing_list,
                $sales_order,
                $status_fee,
                $store,
            ) {

                if ($order_detail->salesOrderOrigin) {
                    collect($order_detail->allSalesOrderOrigin)->each(function ($origin) use (
                        $marketing_buyer_join_date_days,
                        $fee_sharing_data_references,
                        $marketing_get_fee_target,
                        $personel_on_purchase,
                        &$marketing_get_fee,
                        $rm_fee_percentage,
                        $marketing_list,
                        $order_detail,
                        $sales_order,
                        $status_fee,
                        $store,
                    ) {

                        /* marketing get fee target */
                        collect($fee_sharing_data_references)
                            ->each(function ($position) use (
                                $marketing_buyer_join_date_days,
                                &$marketing_get_fee_target,
                                $personel_on_purchase,
                                $rm_fee_percentage,
                                $marketing_list,
                                $order_detail,
                                $sales_order,
                                $status_fee,
                                $origin,
                                $store,
                            ) {

                                $marketing = collect($marketing_list)
                                    ->filter(function ($marketing, $key) use ($position, $sales_order) {
                                        return marketing_position_according_date($marketing, confirmation_time($sales_order)) == $position->position_id;
                                    })
                                    ->first();

                                $origin_for_purchaser = false;

                                /**
                             * purchaser will get fee if join date > 90
                             * or the store is reguler
                             */
                                $marketing_join_days = $marketing_buyer_join_date_days;
                                if ($position->fee_as_marketing == 1) {
                                    $marketing = null;
                                    $origin_for_purchaser = true;

                                    /* fee purchaser */
                                    if ($personel_on_purchase) {
                                        if ($marketing_buyer_join_date_days > 90) {
                                            $marketing = $personel_on_purchase;
                                        }
                                    }
                                }

                                /**
                             * supervisor did not get fee if join date < 91
                             * even the store is reguler
                             */
                                else {
                                    $marketing_join_days = $marketing ? ($marketing->join_date ? Carbon::parse($marketing->join_date)->diffInDays((confirmation_time($sales_order)->endOfDay()), false) : 0) : 0;
                                    if ($marketing_join_days < 91) {
                                        $marketing = null;
                                    }
                                }

                                $detail = [
                                    "marketing_id" => $personel_on_purchase->id,
                                    "origin_for_purchaser" => $origin_for_purchaser,
                                    "personel_id" => $marketing?->id,
                                    "position_id" => $position->position_id,
                                    "position_name" => $position->position->name,
                                    "sales_order_origin_id" => $origin->id,
                                    "sales_order_id" => $sales_order->id,
                                    "type" => $sales_order->type,
                                    "sales_order_detail_id" => $order_detail->id,
                                    "product_id" => $order_detail->product_id,
                                    "quantity_unit" => $origin->quantity_from_origin,
                                    "fee_percentage" => $position ? $position->fee : null,
                                    "status_fee_id" => $sales_order->status_fee_id,
                                    "status_fee_percentage" => $status_fee->percentage,
                                    "join_days" => $marketing_join_days,
                                    "confirmed_at" => confirmation_time($sales_order),
                                ];

                                $marketing_get_fee_target->push(collect($detail));

                                return $detail;
                            });
                    });
                } else {

                    /* marketing get fee target */
                    collect($fee_sharing_data_references)
                        ->each(function ($position) use (
                            $marketing_buyer_join_date_days,
                            &$marketing_get_fee_target,
                            $personel_on_purchase,
                            $rm_fee_percentage,
                            $marketing_list,
                            $order_detail,
                            $sales_order,
                            $status_fee,
                            $store,
                        ) {

                            $marketing = collect($marketing_list)
                                ->filter(function ($marketing, $key) use ($position, $sales_order) {
                                    return marketing_position_according_date($marketing, confirmation_time($sales_order)) == $position->position_id;
                                })
                                ->first();

                            $origin_for_purchaser = false;

                            /**
                         * purchaser will get fee if join date > 90
                         * or the store is reguler
                         */
                            $marketing_join_days = $marketing_buyer_join_date_days;
                            if ($position->fee_as_marketing == 1) {
                                $marketing = null;
                                $origin_for_purchaser = true;

                                /* fee purchaser */
                                if ($personel_on_purchase) {
                                    if ($marketing_buyer_join_date_days > 90) {
                                        $marketing = $personel_on_purchase;
                                    }
                                }
                            }

                            /**
                         * supervisor did not get fee if join date < 91
                         * even the store is reguler
                         */
                            else {
                                $marketing_join_days = $marketing ? ($marketing->join_date ? Carbon::parse($marketing->join_date)->diffInDays((confirmation_time($sales_order)->endOfDay()), false) : 0) : 0;
                                if ($marketing_join_days < 91) {
                                    $marketing = null;
                                }
                            }

                            $detail = [
                                "marketing_id" => $personel_on_purchase->id,
                                "origin_for_purchaser" => $origin_for_purchaser,
                                "personel_id" => $marketing?->id,
                                "position_id" => $position->position_id,
                                "position_name" => $position->position->name,
                                "sales_order_origin_id" => null,
                                "sales_order_id" => $sales_order->id,
                                "type" => $sales_order->type,
                                "sales_order_detail_id" => $order_detail->id,
                                "product_id" => $order_detail->product_id,
                                "quantity_unit" => $order_detail->quantity - $order_detail->returned_quantity,
                                "fee_percentage" => $position ? $position->fee : null,
                                "status_fee_id" => $sales_order->status_fee_id,
                                "status_fee_percentage" => $status_fee->percentage,
                                "join_days" => $marketing_join_days,
                                "confirmed_at" => confirmation_time($sales_order),
                            ];

                            $marketing_get_fee_target->push(collect($detail));

                            return $detail;
                        });
                }

            });

            /**
             * generate fee target sharing
             */
            $marketing_get_fee_target
                ->reject(function ($marketing) {
                    $marketing = (object) $marketing;
                    if (!$marketing["origin_for_purchaser"]) {
                        return !$marketing["personel_id"];
                    }
                })
                ->each(function ($marketing) {
                    $fee_target = $this->fee_target_sharing_origin->create($marketing->toArray());
                });

            $log = $this->log_fee_target_sharing->updateOrCreate([
                "sales_order_id" => $sales_order->id,
                "type" => $sales_order->type,
            ]);

            return "fee target sharing generated";
        }

        return "fee target sharing not generated, from follow up";
    }

    public function feeTargetSharingSpvGeneratorQuery($personel_id, $year, $quarter)
    {
        return $this->fee_target_sharing_origin->query()
            ->with([
                "salesOrderDetail",
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice",
                    ]);
                },
                "salesOrderOrigin" => function ($QQQ) {
                    return $QQQ->with([
                        "direct" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                    ]);
                },
            ])
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->consideredOrder();
            })
            ->where("marketing_id", $personel_id)
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->get();
    }

    /**
     * generate fee target sharing as nominal to supervisor
     * only if product achieve the target on data
     * reference, and marketing match to get
     * fee
     *
     * @return void
     */
    public function feeTargetSharingSpvGenerator($upsert_fee_target_action, $personel_id, $year, $quarter)
    {
        $purchaser_position = DB::table('fee_positions')
            ->whereNull("deleted_at")
            ->where("fee_as_marketing", true)
            ->first();

        $fee_target_sharings = $this->feeTargetSharingSpvGeneratorQuery($personel_id, $year, $quarter)
            ->groupBy("sales_order_id")
            ->reject(function ($origin_per_order) {
                if ($origin_per_order[0]->salesOrder->counter_id) {
                    return $origin_per_order;
                }
            })
            ->flatten();

        $marketing_sales = $fee_target_sharings
            ->filter(function ($origin) use ($purchaser_position) {
                if ($purchaser_position) {
                    return $origin->position_id == $purchaser_position->position_id;
                }
            });

        $marketing_target = $this->feeTargetSharingSpvGeneratorDataMapper($personel_id, $year, $quarter, $marketing_sales, $purchaser_position);

        $spv_get_fee = collect();
        $supervisor_target = $fee_target_sharings
            ->reject(fn($fee) => $fee->personel_id == $personel_id)
            ->where("marketing_id", $personel_id)
            ->whereIn("product_id", $marketing_target->pluck("product_id"))
            ->groupBy([
                function ($val) {return $val->personel_id;},
            ])
            ->each(function ($fee_per_personel, $personel_id) use (
                $fee_target_sharings,
                $purchaser_position,
                $marketing_target,
                &$spv_get_fee,
                $quarter,
                $year,
            ) {
                $fee_target_sharings = $fee_target_sharings->filter(fn($origin) => $origin->personel_id == $personel_id);
                $fee = $this->feeTargetSharingSpvGeneratorDataMapper($personel_id, $year, $quarter, $fee_target_sharings, $purchaser_position, $marketing_target);
                $spv_get_fee->push($fee);
            });

        return $marketing_target
            ->concat($spv_get_fee->flatten(1))
            ->filter(fn($marketing) => $marketing["personel_id"])
            ->sortBy("product_id")
            ->each(function ($marketing_get_fee) use ($upsert_fee_target_action) {
                $upsert_fee_target_action($marketing_get_fee);
            });
    }

    public function feeTargetSharingSpvGeneratorDataMapper($personel_id, $year, $quarter, $fee_target_sharings, $purchaser_position, $marketing_sales = null)
    {
        return $fee_target_sharings
            ->groupBy([
                function ($val) {return $val->position_id;},
                function ($val) {return $val->product_id;},
                function ($val) {return $val->status_fee_id;},
            ])

            ->map(function ($origin_per_position, $position_id) use (
                $purchaser_position,
                $marketing_sales,
                $personel_id,
                $quarter,
                $year,
            ) {
                $fee_position = DB::table('fee_positions')
                    ->whereNull("deleted_at")
                    ->where("position_id", $position_id)
                    ->first();

                /* point total */
                return $origin_per_position
                    ->map(function ($fee_per_product, $product_id) use (
                        $purchaser_position,
                        $marketing_sales,
                        $fee_position,
                        $personel_id,
                        $position_id,
                        $quarter,
                        $year,
                    ) {
                        $fee_per_product = $fee_per_product->map(function ($origin_per_fee_status, $status_fee_id) use (
                            $purchaser_position,
                            $marketing_sales,
                            $fee_position,
                            $personel_id,
                            $position_id,
                            $product_id,
                            $quarter,
                            $year,
                        ) {

                            $fee_product = DB::table('fee_products')
                                ->whereNull("deleted_at")
                                ->where("product_id", $product_id)
                                ->where("type", "2")
                                ->where("year", $year)
                                ->where("quartal", $quarter)
                                ->where("quantity", "<=", collect($origin_per_fee_status)->sum("quantity_unit"))
                                ->orderBy("quantity", "desc")
                                ->first();

                            $marketing_achievement = null;
                            if ($marketing_sales) {
                                $marketing_achievement = $marketing_sales->where("product_id", $product_id)->where("status_fee_id", $status_fee_id)->first();
                            }
                            $status_fee_percentage = $origin_per_fee_status[0]->status_fee_percentage;

                            $detail["personel_id"] = $personel_id;
                            $detail["position_id"] = $position_id;
                            $detail["product_id"] = $product_id;
                            $detail["status_fee_id"] = $status_fee_id;
                            $detail["status_fee_percentage"] = $status_fee_percentage;
                            $detail["year"] = $year;
                            $detail["quarter"] = $quarter;
                            $detail["sales_quantity"] = $marketing_sales ? ($marketing_achievement ? $marketing_achievement["sales_quantity"] : 0) : collect($origin_per_fee_status)->sum("quantity_unit");
                            $detail["sales_fee"] = $marketing_sales ? ($marketing_achievement ? $marketing_achievement["sales_fee"] : 0) : ($fee_product ? collect($origin_per_fee_status)->sum("quantity_unit") * $fee_product->fee : 0);
                            $detail["total_fee"] = $detail["sales_fee"];
                            $detail["target_achieved_quantity"] = collect($origin_per_fee_status)->where("personel_id", $personel_id)->sum("quantity_unit");
                            $detail["target"] = $fee_product ? $fee_product->quantity : null;
                            $detail["fee_per_unit"] = $fee_product ? $fee_product->fee : 0.00;
                            $detail["is_reach_target"] = $fee_product ? true : false;
                            $detail["fee_percentage"] = $fee_position?->fee;
                            $detail["fee_position"] = $detail["sales_quantity"] > 0 ? $detail["sales_fee"] * $detail["target_achieved_quantity"] / $detail["sales_quantity"] * $detail["fee_percentage"] / 100 : 0;
                            $detail["fee_shared"] = $detail["fee_position"] * $detail["status_fee_percentage"] / 100;

                            /*
                    |--------------------------
                    | AKTIF TARGET
                    |-------------------
                     */
                            $origin_per_fee_statuses = $origin_per_fee_status
                                ->groupBy("sales_order_id")
                                ->reject(fn($origin_per_order, $sales_order_id) => !$this->isSettle($origin_per_order[0]->salesOrder))
                                ->flatten();

                            $origin_per_fee_status_active = $origin_per_fee_statuses
                                ->groupBy("sales_order_id")
                                ->filter(fn($origin_per_order) => $origin_per_order[0]->salesOrder->status == "confirmed")
                                ->flatten();

                            $origin_per_fee_status_active = $this->feeTargetSharingSpvGeneratorDataMapperActive($origin_per_fee_status_active, "confirmed");

                            $detail["sales_quantity_active"] = $marketing_sales ? ($marketing_achievement ? $marketing_achievement["sales_quantity_active"] : 0) : collect($origin_per_fee_statuses)->sum("quantity_unit");
                            $detail["sales_fee_active"] = $marketing_sales ? ($marketing_achievement ? $marketing_achievement["sales_fee_active"] : 0) : ($fee_product ? collect($origin_per_fee_statuses)->sum("quantity_unit") * $fee_product->fee : 0);
                            $detail["target_achieved_quantity_active"] = collect($origin_per_fee_status_active)->where("personel_id", $personel_id)->sum("quantity_unit");
                            $detail["fee_percentage_active"] = $fee_position?->fee;
                            $detail["fee_position_active"] = $detail["sales_quantity_active"] > 0 ? $detail["sales_fee_active"] * $detail["target_achieved_quantity_active"] / $detail["sales_quantity_active"] * $detail["fee_percentage_active"] / 100 : 0;
                            $detail["status_fee_percentage_active"] = $origin_per_fee_status_active->count() > 0 ? $origin_per_fee_status_active[0]?->status_fee_percentage : 100;
                            $detail["fee_shared_active"] = $detail["fee_position_active"] * $detail["status_fee_percentage_active"] / 100;

                            $origin_per_fee_status_active_pending = $origin_per_fee_statuses
                                ->groupBy("sales_order_id")
                                ->filter(fn($origin_per_order) => $origin_per_order[0]->salesOrder->status == "confirmed")
                                ->flatten();

                            $origin_per_fee_status_active_pending = $this->feeTargetSharingSpvGeneratorDataMapperActive($origin_per_fee_status, "pending");

                            $detail["target_achieved_quantity_active_pending"] = collect($origin_per_fee_status_active_pending)->where("personel_id", $personel_id)->sum("quantity_unit");
                            $detail["fee_percentage_active_pending"] = $fee_position?->fee;
                            $detail["fee_position_active_pending"] = $detail["sales_quantity_active"] > 0 ? $detail["sales_fee_active"] * $detail["target_achieved_quantity_active_pending"] / $detail["sales_quantity_active"] * $detail["fee_percentage_active_pending"] / 100 : 0;
                            $detail["status_fee_percentage_active_pending"] = $origin_per_fee_status_active_pending->count() > 0 ? $origin_per_fee_status_active_pending[0]?->status_fee_percentage : 100;
                            $detail["fee_shared_active_pending"] = $detail["fee_position_active_pending"] * $detail["fee_percentage_active_pending"] / 100;

                            return $detail;
                        });

                        return $fee_per_product->values();
                    })
                    ->values();
            })
            ->values()
            ->flatten(2)
            ->filter(fn($fee) => $fee["is_reach_target"]);
    }

    public function feeTargetSharingSpvGeneratorDataMapperActive($origin_per_fee_status, $status = "confirmed")
    {
        return $origin_per_fee_status
            ->groupBy("sales_order_id")

            /* pending and confirmed order will separeted */
            ->filter(fn($origin_per_order) => $origin_per_order[0]->salesOrder->status == $status)

            /**
         * reject if order was direct and settle beyond maturity,
         * and indirect sales considerd as settle
         */
            ->reject(fn($origin_per_order, $sales_order_id) => !$this->isSettleBeforeMaturity($origin_per_order[0]->salesOrder))
            ->flatten()
            ->groupBy("sales_order_origin_id")
            ->reject(function ($origin_so_origin, $sales_order_origin_id) {

                /**
             * indirect sale will counted as active if direct origin
             * from it indirect sale also counted as active
             * (rule: settle < maximum_settle_days)
             */
                if ($sales_order_origin_id) {

                    if ($origin_so_origin[0]->salesOrder->type == "2" && $origin_so_origin[0]->salesOrderOrigin) {
                        if ($origin_so_origin[0]->salesOrderOrigin->direct) {
                            if ($origin_so_origin[0]->salesOrderOrigin->direct->invoice->payment_status == "settle") {

                                /* payment time proforma according fee position maximum settle days */
                                if (PaymentTimeForFee::paymentTimeForFeeCalculation($origin_so_origin[0]->salesOrderOrigin?->direct?->invoice) > maximum_settle_days(confirmation_time($origin_so_origin[0]->salesOrderOrigin?->direct)->format("Y"))) {
                                    return $origin_so_origin;
                                }
                            } else if ($origin_so_origin[0]->salesOrderOrigin->direct->invoice->payment_status != "settle") {
                                return $origin_so_origin;
                            }
                        }
                    }

                }
            })
            ->flatten();
    }

    /**
     * fee target sharing for spesific marketing in this year and quarter
     *
     * @param [type] $personel
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeSharingTargetSpesificMarketing($personel, $year, $quarter)
    {
        $personel_child = collect($this->getChildren($personel->id))->reject(fn($personel_id) => $personel_id == $personel->id)->toArray();

        $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
            ->with([
                "salesOrder.personel.supervisorInConfirmedOrder",
                "statusFee",
            ])
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->where("is_returned", false)
            ->countedFeeAccordingOrigin()
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->confirmedOrder();
            })
            ->where(function ($QQQ) use ($personel, $personel_child) {
                return $QQQ
                    ->where("personel_id", $personel->id)

                    /* as marketing and get no fee */
                    ->orWhere(function ($QQQ) use ($personel, $personel_child) {
                        return $QQQ
                            ->whereNull("personel_id")
                            ->whereHas("salesOrder", function ($QQQ) use ($personel, $personel_child) {
                                return $QQQ->where("personel_id", $personel->id);
                            });
                    })
                    /* as spv and get no fee */
                    ->orWhere(function ($QQQ) use ($personel, $personel_child) {
                        return $QQQ
                            ->whereNull("personel_id")
                            ->whereHas("salesOrder", function ($QQQ) use ($personel, $personel_child) {
                                return $QQQ
                                    ->whereIn("personel_id", $personel_child)
                                    ->where("personel_id", "!=", $personel->id);
                            });
                    });
            })
            ->get()
            ->groupBy("sales_order_id")
            ->reject(function ($origin) {
                if ($origin[0]->salesOrder->counter_id) {
                    return $origin;
                }
            })
            ->flatten()
            ->map(function ($origin) use ($personel) {
                $origin->personel_updated = $personel;
                return $origin;
            });

        return $fee_target_sharing_origin;
    }

    /**
     * fee target sharing data mapping this year and quarter
     *
     * @param [type] $fee_target_sharing_origins
     * @return void
     */
    public function feeTargetSharingOriginDataMapping($fee_target_sharing_origins)
    {
        return $fee_target_sharing_origins
            ->map(function ($origin) use (&$marketing_buyer_join_date_days) {

                /**
             * if marketing as purchaser
             * exist
             */
                $personel_on_purchase = $origin->personel_updated;
                if (!empty($personel_on_purchase)) {
                    $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(Carbon::parse($origin->confirmed_at), false) : 0;
                }

                $spv_marketing_purchaser_in_order = null;
                if ($origin->salesOrder->personel_id == $origin->personel_updated->id) {
                    $spv_marketing_purchaser_in_order = $origin->personel_updated->id;
                } else {
                    $spv_marketing_purchaser_in_order = $origin->salesOrder->personel->supervisorInConfirmedOrder ? json_decode($origin->salesOrder->personel->supervisorInConfirmedOrder->properties)->attributes->supervisor_id : null;
                }

                $origin->spv_marketing_purchaser_in_order = $spv_marketing_purchaser_in_order;
                $origin->marketing_purchaser = $origin->salesOrder->personel_id;
                $origin->status_fee_name = $origin->statusFee->name;
                $origin->marketing_buyer_join_date_days = $marketing_buyer_join_date_days;

                $as_marketing = $origin->salesOrder->personel_id == $origin->personel_updated->id ? true : false;
                $origin->as_marketing = $as_marketing;

                /**
             * as marketing if join days less than 90 and status fee name is not R then this marketing
             * will not get fee for this origin, as spv if join days less then 90 no matter
             * what it's origin status fee name, then this marketing updated will not
             * get fee, important resign date must more then origin confirmed_at
             */
                $set_to_null = false;
                if (($marketing_buyer_join_date_days < 90 && $origin->status_fee_name != "R" && $as_marketing)
                    || (!empty($origin->personel_updated->resign_date) && $origin->personel_updated->resign_date <= Carbon::parse($origin->confirmed_at)->format("Y-m-d"))
                    || ($marketing_buyer_join_date_days < 90 && !$as_marketing)
                ) {
                    $set_to_null = true;
                }

                /**
             * if join days more than 90 or status fee name is R and act as marketing then this marketing
             * will get fee for this origin, important resign date must more then origin confirmed_at
             */
                $set_to_get_fee = false;
                if (($marketing_buyer_join_date_days >= 90 || ($origin->status_fee_name == "R" && $as_marketing))
                    && ($origin->personel_updated->resign_date > Carbon::parse($origin->confirmed_at)->format("Y-m-d") || empty($origin->personel_updated->resign_date))
                ) {
                    $set_to_get_fee = true;
                }

                $origin->set_to_null = $set_to_null;
                $origin->set_to_get_fee = $set_to_get_fee;

                $origin->unsetRelation("salesOrder");
                $origin->unsetRelation("statusFee");
                return $origin;
            })
            ->reject(function ($origin) use ($marketing_buyer_join_date_days) {
                return $origin->personel_id != $origin->spv_marketing_purchaser_in_order && $origin->personel_id != null;
            })
            ->map(function ($origin) {
                $personel_id = null;
                if ($origin->set_to_null) {
                    $origin->personel_id = null;
                } else {
                    $personel_id = $origin->personel_id;
                }

                if ($origin->set_to_get_fee) {
                    $origin->personel_id = $origin->personel_updated->id;
                    $personel_id = $origin->personel_updated->id;;
                }

                $update = FeeTargetSharingSoOrigin::query()
                    ->where("id", $origin->id)
                    ->update([
                        "personel_id" => $personel_id,
                    ]);

                return collect($origin)->forget("personel_updated");
            });
    }
}
