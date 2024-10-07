<?php

namespace Modules\Personel\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Traits\SalesOrderTrait;

/**
 * Fee marketing recalculate
 */
trait FeeMarketingTraitV2
{
    use SalesOrderTrait;

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
                    return $QQQ->confirmedOrder();
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
            ->countedFeeAccordingOrigin()
            ->where("is_returned", false)
            ->where("is_checked", true)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->confirmedOrder();
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
        /**
         * follow up order will get fee if follow up days
         * more than 60 days
         */
        $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

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
                return $QQQ->where(function ($QQQ) use ($year, $quarter) {
                    return $QQQ
                        ->considerOrderStatusForFeeMarketing()
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->whereDoesntHave("salesOrderOrigin")
                                ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                                    return $QQQ->where("is_fee_counted", true);
                                });
                        })
                        ->where(function ($QQQ) use ($year, $quarter) {
                            return $QQQ
                                ->where(function ($QQQ) use ($year, $quarter) {
                                    return $QQQ
                                        ->where("type", "1")
                                        ->whereHas("invoice", function ($QQQ) use ($year, $quarter) {
                                            return $QQQ
                                                ->where("payment_status", "settle")
                                                ->whereYear("created_at", $year);
                                        });
                                })
                                ->orWhere(function ($QQQ) use ($year, $quarter) {
                                    return $QQQ
                                        ->where("type", "2")
                                        ->whereYear("date", $year);
                                });
                        });
                });
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
        return $fee_sharings
            ->each(function ($origin) {
                $log = $this->log_marketing_fee_counter->updateOrCreate([
                    "fee_sharing_origin_id" => $origin->id,
                    "sales_order_id" => $origin->salesOrder->id,
                    "type" => "reguler",
                ]);
            })
            ->groupBy("sales_order_id")
            ->flatten()
            ->groupBy("sales_order_detail_id")
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
     * fee origin data mapping
     *
     * @param [type] $fee_sharings
     * @return void
     */
    public function feeMarketingRegulerActiveDataMapping($fee_sharings, $status = "confirmed")
    {
        return $fee_sharings
            ->groupBy("sales_order_id")

            /* pending and confirmed order will separeted */
            ->filter(fn($origin_per_order) => $origin_per_order[0]->salesOrder->status == $status)

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
                    if ($origin[0]->salesOrderOrigin?->direct?->invoice?->payment_time > maximum_settle_days(confirmation_time($origin[0]->salesOrderOrigin?->direct)->format("Y"))) {
                        return $origin;
                    }
                } elseif ($origin[0]->salesOrder->type == "2" && $origin[0]->salesOrderOrigin?->direct?->invoice?->payment_status != "settle") {
                    return $origin;
                }
            })
            ->each(function ($origin_per_order_detail) {
                $log = $this->log_marketing_fee_counter->updateOrCreate([
                    "fee_sharing_origin_id" => $origin_per_order_detail[0]->id,
                    "sales_order_id" => $origin_per_order_detail[0]->salesOrder->id,
                    "type" => "reguler",
                ], [
                    "is_settle" => true,
                ]);
            })
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
     * fee target marketing total
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function feeMarketingTargetTotal($personel_id, $year, $quarter)
    {
        $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
            ->with([
                "feeProduct" => function ($QQQ) use ($year) {
                    return $QQQ
                        ->where("year", $year)
                        ->where("type", "2");
                },
                "salesOrder",
            ])
            ->where("personel_id", $personel_id)
            ->countedFeeAccordingOrigin()
            ->whereYear("confirmed_at", $year)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->confirmedOrder();
            })
            ->where("is_returned", false)
            ->get()
            ->groupBy("sales_order_id")
            ->reject(function ($origin) {
                if ($origin[0]->salesOrder->counter_id) {
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

        return $fee_target_sharing_origin;
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
                return $QQQ->where(function ($QQQ) use ($year, $quarter) {
                    return $QQQ
                        ->considerOrderStatusForFeeMarketing()
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->whereDoesntHave("salesOrderOrigin")
                                ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                                    return $QQQ->where("is_fee_counted", true);
                                });
                        })
                        ->where(function ($QQQ) use ($year, $quarter) {
                            return $QQQ
                                ->where(function ($QQQ) use ($year, $quarter) {
                                    return $QQQ
                                        ->where("type", "1")
                                        ->whereHas("invoice", function ($QQQ) use ($year, $quarter) {
                                            return $QQQ
                                                ->where("payment_status", "settle")
                                                ->whereYear("created_at", $year);
                                        });
                                })
                                ->orWhere(function ($QQQ) use ($year, $quarter) {
                                    return $QQQ
                                        ->where("type", "2")
                                        ->whereYear("date", $year);
                                });
                        });
                });
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
                    if ($origin[0]->salesOrderOrigin?->direct?->invoice?->payment_time > maximum_settle_days(confirmation_time($origin[0]->salesOrderOrigin?->direct)->format("Y"))) {
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

                $update = FeeSharingSoOrigin::query()
                    ->where("id", $origin->id)
                    ->update([
                        "personel_id" => $personel_id,
                    ]);

                return collect($origin)->forget("personel_updated");
            });
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
                $active_contract = $this->distributorActiveContract($sales_order->store_id);

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
                $quantity = $detail->quantity;
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
                    if (is_affected_from_return($sales_order)) {
                        $fee_reguler = 0;
                    }

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
                ->get()
                ->each(function ($origin) {
                    $origin->delete();
                });
        }

        $active_contract = $this->distributorActiveContract($sales_order->store_id);

        if (!$active_contract && empty($sales_order->afftected_by_return) && !is_return_order_exist($sales_order->store_id, $sales_order->personel_id, confirmation_time($sales_order)->format("Y"), confirmation_time($sales_order)->quarter)) {

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
            $marketing_list = [];
            $marketing_supervisor = $this->parentPersonel($sales_order->personel_id, confirmation_time($sales_order) ? confirmation_time($sales_order)->format("Y-m-d") : $sales_order->created_at->format("Y-m-d"));

            $marketing_list = $this->personel->query()
                ->with("position.fee")
                ->whereNull("deleted_at")
                ->whereIn("id", $marketing_supervisor)
                ->get();

            $marketing_list = collect($marketing_list)->reject(fn($marketing) => $marketing->status == "3" || ($marketing->resign_date ? $marketing->resign_date <= confirmation_time($sales_order)->format("Y-m-d") : null));

            $all_spv = $marketing_list;

            /* get status fee hand over status */
            $status_fee = $sales_order->statusFee;

            /* rm fee percentage */
            $rm_fee_percentage = $this->fee_position
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->where("fee_as_marketing", "1");
                })
                ->first();

            /* fee position list excludeing purchaser marketing */
            $fee_sharing_data_references = $this->fee_position
                ->whereHas("position", function ($QQQ) {
                    return $QQQ;
                })
                ->orderBy("fee")
                ->get();

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
            $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

            /**
             * fee cut according sales counter
             */
            $is_fee_cut_for_sc = false;
            $sc_reduction_percentage = $fee_sharing_data_references->first()->fee_sc_on_order;

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

                    /* sc fee reduction percentage */
                    $sc_fee_reduction = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->get();
                    $sc_fee_reduction->each(function ($fee_sc) use (&$sc_reduction_percentage, $sales_order) {
                        if ($sales_order->follow_up_days > $fee_sc->follow_up_days) {
                            $sc_reduction_percentage = $fee_sc->fee;
                        }
                    });

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
                if ($marketing_buyer_join_date_days >= 90 && $status_fee->name != "R") {

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
                        ->filter(function ($marketing, $key) use ($fee) {
                            return $marketing->position_id == $fee->position_id;
                        })
                        ->first();

                    /* personel purchaser */
                    $fee_status = null;
                    if ($fee->fee_as_marketing == 1) {
                        $personel_id = $personel_on_purchase;
                        $fee_status = "purchaser";
                    }

                    /* applicator fee */
                    if ($fee->is_applicator != null) {
                        $fee_status = "applicator";
                    } else if ($fee->is_mm != null) {
                        $fee_status = "marketing manager";
                    }

                    /* check join date */
                    $marketing_join_days = $personel_id ? ($personel_id->join_date ? Carbon::parse($personel_id->join_date)->diffInDays(confirmation_time($sales_order), false) : 0) : 0;

                    if ($marketing_join_days < 90 && $status_fee->name !== "R") {
                        $personel_id = null;
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

                $log = $this->fee_sharing_origin->updateOrCreate([
                    "personel_id" => $marketing->personel_id,
                    "position_id" => $marketing->position_id,
                    "sales_order_detail_id" => $marketing->sales_order_detail_id,
                    "sales_order_id" => $marketing->sales_order_id,
                    "fee_status" => $marketing->fee_status,
                ], [
                    "fee_percentage" => $marketing->fee_percentage,
                    "sales_order_origin_id" => $marketing->sales_order_origin_id,
                    "handover_status" => $marketing->handover_status,
                    "status_fee" => $marketing->status_fee,
                    "confirmed_at" => $marketing->confirmed_at,
                    "sc_reduction_percentage" => $marketing->sc_reduction_percentage,
                ]);

            }

            /**
             * follow up order will get fee if follow up days
             * more than 60 days
             */
            $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

            /**
             * clean up fee sharing if there any of changes from
             * sales order, fee sharing must update according
             * sales order or fee position data reference
             * for example if fee sharing contain sales
             * counter but sales order change and
             * mark as non follow up, then fee
             * sharing must delete fee for
             * sales counter in this
             * sales order fee
             * sharing
             */
            $delete_fee_sharing = $this->fee_sharing_origin->query()
                ->where("sales_order_id", $sales_order->id)
                ->where(function ($QQQ) use ($sales_order, $follow_up_days_reference, $fee_sharing_data_references) {
                    return $QQQ
                        ->where(function ($QQQ) use ($sales_order, $follow_up_days_reference, $fee_sharing_data_references) {
                            return $QQQ
                                ->whereNotIn("position_id", $fee_sharing_data_references->pluck("position_id"))
                                ->where("fee_status", "!=", "sales counter");
                        })

                        ->orWhere(function ($QQQ) use ($sales_order, $follow_up_days_reference) {
                            return $QQQ
                                ->when(!$this->isFollowUp($sales_order, $follow_up_days_reference), function ($QQQ) {
                                    return $QQQ->where("fee_status", "sales counter");
                                });
                        });
                })
                ->delete();

            return "fee sharing generated, total: " . $marketing_get_fee->count();
        }

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
                "salesOrderDetail" => function ($QQQ) {
                    return $QQQ->with([
                        "allFeeProduct",
                    ]);
                },
                "feePosition",
            ])
            ->whereHas("salesOrderDetail")
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->considerOrderStatusForFeeMarketing();
            })
            ->where("sales_order_id", $sales_order->id)
            ->get();

        $marketing_fee = collect([]);

        /* add quantity */
        $fee_sharing_origins = $fee_sharing_origins
            ->map(function ($origin) {
                $origin["quantity"] = $origin->salesOrderDetail->quantity;

                /**
                 * pending at the moment, it's becouse if there any update of quantity
                 * the main reference of quanaity is qaitniy on detail order
                 * not in origin, so origin need to be update
                 */
                // if ($origin->salesOrderOrigin) {
                //     $origin->quantity = $origin->salesOrderOrigin->quantity_from_origin;
                // }
                return $origin;
            })
            ->groupBy("sales_order_detail_id")
            ->each(function ($origin_per_detail, $order_detail_id) use (&$marketing_fee) {
                $quantity = $origin_per_detail->first()->quantity;
                $fee = 0;
                $fee_product = null;

                /* get fee target which match with quantity */
                $fee_product = $origin_per_detail->first()->salesOrderDetail->allFeeProduct
                    ->where('type', 1)
                    ->where("year", Carbon::parse($origin_per_detail[0]->confirmed_at)->format("Y"))
                    ->where("quartal", Carbon::parse($origin_per_detail[0]->confirmed_at)->format("Y"))
                    ->first();

                /* if there product has fee product */
                if (!$fee_product) {

                    /* if there have no fee target */
                    $fee_sharing_origin = $this->fee_sharing_origin
                        ->where("sales_order_detail_id", $origin_per_detail->first()->sales_order_detail_id)
                        ->update([
                            "is_checked" => "1",
                        ]);

                    /* retrun true for continue */
                    return true;

                } else {

                    if (!$fee_product) {

                        /* if there have no fee target */
                        $fee_sharing_origin = $this->fee_sharing_origin
                            ->where("sales_order_detail_id", $origin_per_detail->first()->sales_order_detail_id)
                            ->update([
                                "is_checked" => "1",
                            ]);

                        /* retrun true for continue */
                        return true;
                    }
                }

                $fee_reguler = $quantity * $fee_product->fee;

                /**
                 * |-------------------------------------------------------------------------
                 * | fee sharing distribution to marketing according fee sharing origin percentage
                 * |-------------------------------------------------------------------------
                 */

                /* check status fee */
                $is_handover = $origin_per_detail
                    ->filter(function ($fee, $key) use ($fee_reguler) {
                        return $fee->handover_status == 1;
                    })
                    ->first();

                /**
                 *
                 * fill fee reguler shared and
                 * fee target shared
                 */
                $sales_counter = $origin_per_detail
                    ->filter(function ($fee, $key) {
                        $fee->unsetRelation("salesOrder");
                        return $fee->fee_status == "sales counter";
                    })
                    ->first();

                /**
                 *
                 * fee sharing purchaser
                 */
                $fee_purchaser = $origin_per_detail
                    ->filter(function ($fee, $key) {
                        $fee->unsetRelation("salesOrder");
                        return $fee->fee_status == "purchaser";
                    })
                    ->first();

                $origin_per_detail->map(function ($fee, $key) use ($fee_reguler, $is_handover, $marketing_fee, $sales_counter, $fee_purchaser) {
                    $marketing_fee_reguler = 0;
                    $marketing_fee_reguler_before_cut = 0;

                    /* sales counter fee check */
                    if ($sales_counter) {

                        /**
                         * fee will be cut for sales counter fom follow up with that rules
                         * only reguler fee cuts on follow up
                         * target fee does not change
                         */

                        if ($fee->feePosition) {

                            $marketing_fee_reguler = ($fee_reguler * $fee->fee_percentage / 100) - (($fee_reguler * $fee->fee_percentage / 100) * ($sales_counter ? ($fee->sc_reduction_percentage ? $fee->sc_reduction_percentage : 100) : 100) / 100);
                        }
                    } else {

                        /* status fee dealer / sub dealer check */
                        if ($fee->fee_status == "marketing fee hand over") {
                            $marketing_fee_reguler = ($fee_reguler * $fee_purchaser->fee_percentage / 100) * $fee->fee_percentage / 100;
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
                        "fee_status" => $fee->fee_status,
                        "sales_order_origin_id" => $fee->sales_order_origin_id,
                        "sales_order_id" => $fee->sales_order_id,
                        "sales_order_detail_id" => $fee->sales_order_detail_id,
                        "total_fee" => $fee->salesOrderDetail->marketing_fee_reguler,
                    ]));

                    $fee->unsetRelation("salesOrder");
                });

                /**
                 * purchaser fee normal
                 */
                $marketing_fee_per_detail = $marketing_fee->sortBy("fee_percentage")->where("sales_order_detail_id", $order_detail_id)->values();
                $fee_purchaser = $marketing_fee_per_detail
                    ->filter(function ($fee, $key) {
                        return $fee["fee_status"] == "purchaser";
                    })
                    ->first();

                /**
                 *
                 * fee purchaser according fee follow up
                 */
                if ($is_handover) {
                    $fee_sharing_status_fee = $marketing_fee_per_detail
                        ->filter(function ($fee, $key) {
                            return $fee["fee_status"] == "marketing fee hand over";
                        })
                        ->first();

                    /* fee follow up */
                    $marketing_fee_per_detail = $marketing_fee_per_detail->map(function ($fee, $key) use ($fee_purchaser, $fee_sharing_status_fee, $sales_counter) {
                        if ($fee["fee_status"] == "marketing fee hand over") {
                            $fee["fee_shared"] = $fee_purchaser["fee_shared"] * ($fee_sharing_status_fee ? $fee_sharing_status_fee["fee_percentage"] / 100 : 1);
                            $fee["fee_shared_before_cut"] = $fee_purchaser["fee_shared"];
                        }

                        return $fee;
                    });
                }

                /**
                 * fee sales counter
                 */
                $fee_position = $this->fee_position->all();
                $fee_according_position = $fee_position->where("follow_up", "1")->pluck("position_id")->toArray();
                $fee_sales_counter = $marketing_fee_per_detail
                    ->filter(function ($fee, $key) use ($fee_according_position, $is_handover) {
                        if ($is_handover) {
                            return in_array($fee["position_id"], $fee_according_position) && $fee["fee_status"] != "marketing fee hand over" && $fee["fee_status"] != "sales counter";
                        } else {
                            return in_array($fee["position_id"], $fee_according_position) && $fee["fee_status"] != "sales counter";
                        }
                    })
                    ->sum("fee_shared");

                /**
                 * update fee sharing origin
                 */
                $marketing_fee_per_detail = $marketing_fee_per_detail->map(function ($fee, $key) use ($fee_sales_counter, $fee_reguler) {
                    if ($fee["fee_status"] == "sales counter") {
                        $fee["fee_shared"] = $fee_sales_counter;
                    }

                    /* update fee shairing */
                    $fee_sharing_update = $this->fee_sharing_origin->query()
                        ->where("id", $fee["id"])
                        ->update([
                            "fee_shared" => $fee["fee_shared"],
                            "is_checked" => 1,
                            "total_fee" => $fee_reguler,
                        ]);

                    return $fee;
                });

            });

        return "fee sharing calculated";
    }

    /**
     * fee target sharing origin generator
     *
     * @param [type] $sales_order
     * @return void
     */
    public function feeTargetSharingOriginGenerator($sales_order, $delete_origin = true, $active_contract = null)
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

        /* order was confirmed or has nota date (date) for indirect */
        if (confirmation_time($sales_order)) {
            if (empty($sales_order->counter_id) && empty($sales_order->afftected_by_return) && !is_return_order_exist($sales_order->store_id, $sales_order->personel_id, confirmation_time($sales_order)->format("Y"), confirmation_time($sales_order)->quarter)) {

                /* check contract in case parameter is missing */
                if (!$active_contract) {
                    $active_contract = $this->distributorActiveContract($sales_order->store_id);
                }

                if (!$active_contract) {

                    /**
                     * save log firdt time
                     */
                    $log = $this->log_fee_target_sharing->updateOrCreate([
                        "sales_order_id" => $sales_order->id,
                        "type" => $sales_order->type,
                    ]);

                    /* get marketing with all his supervisor to top supervisor */
                    $marketing_list = [];
                    $marketing_supervisor = $this->parentPersonel($sales_order->personel_id, confirmation_time($sales_order) ? confirmation_time($sales_order)->format("Y-m-d") : $sales_order->created_at->format("Y-m-d"));
                    $marketing_list = $this->personel->with("position.fee")->whereNull("deleted_at")->whereIn("id", $marketing_supervisor)->get();

                    /* filter supervisor active and Haven't resigned yet */
                    $marketing_list = collect($marketing_list)->filter(function ($marketing, $key) {
                        return $marketing->status !== "3" && ($marketing->resign_date >= now() || $marketing->resign_date == null);
                    });

                    /**
                     * fee position
                     */
                    $fee_position = $this->fee_position->query()
                        ->with([
                            "position" => function ($QQQ) {
                                return $QQQ->with([
                                    "fee",
                                ]);
                            },
                        ])
                        ->whereHas("position")
                        ->get();

                    /* rm fee percentage */
                    $rm_fee_percentage = $fee_position
                        ->where("fee_as_marketing", "1")
                        ->first();

                    /**
                     * fee is for marketing on purchase
                     */
                    $personel_on_purchase = $this->personel->query()
                        ->with([
                            "position" => function ($QQQ) {
                                return $QQQ->with("fee");
                            },
                        ])
                        ->where("id", $sales_order->personel_id)
                        ->first();

                    /* get status fee hand over status */
                    $status_fee = $sales_order->statusFee;

                    /**
                     * marketing fee cut according follow up
                     */
                    $marketing_buyer_join_date_days = 0;

                    /**
                     * if marketing as purchaser
                     * exist
                     */
                    if (!empty($personel_on_purchase)) {
                        $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(now(), false) : 0;
                    }

                    /* get store hand over status */
                    $store = null;
                    if ($sales_order->dealer !== null) {
                        $store = $sales_order->dealer;
                    } else {
                        $store = $sales_order->subDealer;
                    }

                    /* handover store from another marketing */
                    $is_handover = $sales_order->statusFee;

                    $marketing_get_fee_target = collect();
                    $sales_order->salesOrderDetail->each(function ($order_detail) use (
                        $marketing_buyer_join_date_days,
                        &$marketing_get_fee_target,
                        $personel_on_purchase,
                        $rm_fee_percentage,
                        $marketing_list,
                        $fee_position,
                        $sales_order,
                        $is_handover,
                        $status_fee,
                        $store,
                    ) {

                        if ($order_detail->salesOrderOrigin) {
                            collect($order_detail->allSalesOrderOrigin)->each(function ($origin) use (
                                $marketing_buyer_join_date_days,
                                $marketing_get_fee_target,
                                $personel_on_purchase,
                                &$marketing_get_fee,
                                $rm_fee_percentage,
                                $marketing_list,
                                $order_detail,
                                $fee_position,
                                $sales_order,
                                $is_handover,
                                $status_fee,
                                $store,
                            ) {

                                /* marketing get fee target */
                                collect($fee_position)
                                    ->each(function ($position) use (
                                        $marketing_buyer_join_date_days,
                                        &$marketing_get_fee_target,
                                        $personel_on_purchase,
                                        $rm_fee_percentage,
                                        $marketing_list,
                                        $order_detail,
                                        $is_handover,
                                        $sales_order,
                                        $origin,
                                        $store,
                                    ) {

                                        $marketing = $marketing_list->where("position_id", $position->position_id)->first();
                                        $detail = null;

                                        if ($position->fee_as_marketing == 1) {
                                            $personel_id = null;

                                            /* fee purchaser */
                                            if ($personel_on_purchase) {
                                                if ($marketing_buyer_join_date_days >= 90 || $sales_order->statusFee->name == "R") {
                                                    $personel_id = $personel_on_purchase->id;
                                                }
                                            }

                                            $detail = [
                                                "personel_id" => $personel_id,
                                                "position_id" => $rm_fee_percentage->position_id,
                                                "position_name" => $rm_fee_percentage->position->name,
                                                "sales_order_origin_id" => $origin->id,
                                                "sales_order_id" => $sales_order->id,
                                                "type" => $sales_order->type,
                                                "sales_order_detail_id" => $order_detail->id,
                                                "product_id" => $order_detail->product_id,
                                                "quantity_unit" => $order_detail->quantity,
                                                "fee_percentage" => $rm_fee_percentage->position->fee ? $rm_fee_percentage->position->fee->fee : $rm_fee_percentage->fee,
                                                "status_fee_id" => $sales_order->status_fee_id,
                                                "status_fee_percentage" => $is_handover->percentage,
                                                "fee_nominal" => 0,
                                                "join_days" => $marketing_buyer_join_date_days,
                                                "confirmed_at" => confirmation_time($sales_order),
                                            ];

                                            $marketing_get_fee_target->push(collect($detail));
                                        } else {

                                            $marketing_join_days = $marketing ? ($marketing->join_date ? Carbon::parse($marketing->join_date)->diffInDays((confirmation_time($sales_order)), false) : 0) : 0;

                                            if ($marketing_join_days < 90 && $sales_order->statusFee->name !== "R") {
                                                $marketing = null;
                                            }

                                            $detail = [
                                                "personel_id" => $marketing ? $marketing->id : null,
                                                "position_id" => $position->position_id,
                                                "position_name" => $position->position->name,
                                                "sales_order_origin_id" => $origin->id,
                                                "sales_order_id" => $sales_order->id,
                                                "type" => $sales_order->type,
                                                "sales_order_detail_id" => $order_detail->id,
                                                "product_id" => $order_detail->product_id,
                                                "quantity_unit" => $order_detail->quantity,
                                                "fee_percentage" => $position ? $position->fee : null,
                                                "status_fee_id" => $sales_order->status_fee_id,
                                                "status_fee_percentage" => $is_handover->percentage,
                                                "fee_nominal" => 0,
                                                "join_days" => $marketing_join_days,
                                                "confirmed_at" => confirmation_time($sales_order),
                                            ];

                                            $marketing_get_fee_target->push(collect($detail));
                                        }

                                        return $detail;
                                    });

                            });
                        } else {

                            /* marketing get fee target */
                            collect($fee_position)
                                ->each(function ($position) use (
                                    $marketing_buyer_join_date_days,
                                    &$marketing_get_fee_target,
                                    $personel_on_purchase,
                                    $rm_fee_percentage,
                                    $marketing_list,
                                    $order_detail,
                                    $sales_order,
                                    $is_handover,
                                    $store,
                                ) {
                                    $marketing = $marketing_list->where("position_id", $position->position_id)->first();
                                    $detail = null;

                                    if ($position->fee_as_marketing == 1) {
                                        $personel_id = null;

                                        /* fee purchaser */
                                        if ($personel_on_purchase) {
                                            if ($marketing_buyer_join_date_days >= 90 || $sales_order->statusFee->name == "R") {
                                                $personel_id = $personel_on_purchase->id;
                                            }
                                        }

                                        $detail = [
                                            "personel_id" => $personel_id,
                                            "position_id" => $rm_fee_percentage->position_id,
                                            "position_name" => $rm_fee_percentage->position->name,
                                            "sales_order_origin_id" => null,
                                            "sales_order_id" => $sales_order->id,
                                            "type" => $sales_order->type,
                                            "sales_order_detail_id" => $order_detail->id,
                                            "product_id" => $order_detail->product_id,
                                            "quantity_unit" => $order_detail->quantity,
                                            "fee_percentage" => $rm_fee_percentage->position->fee ? $rm_fee_percentage->position->fee->fee : $rm_fee_percentage->fee,
                                            "status_fee_id" => $sales_order->status_fee_id,
                                            "status_fee_percentage" => $is_handover->percentage,
                                            "fee_nominal" => 0,
                                            "join_days" => $marketing_buyer_join_date_days,
                                            "confirmed_at" => confirmation_time($sales_order),
                                        ];

                                        $marketing_get_fee_target->push(collect($detail));

                                    } else {

                                        $marketing_join_days = $marketing ? ($marketing->join_date ? Carbon::parse($marketing->join_date)->diffInDays(confirmation_time($sales_order), false) : 0) : 0;

                                        if ($marketing_join_days < 90 && $sales_order->statusFee->name !== "R") {
                                            $marketing = null;
                                        }

                                        $detail = [
                                            "personel_id" => $marketing ? $marketing->id : null,
                                            "position_id" => $position->position_id,
                                            "position_name" => $position->position->name,
                                            "sales_order_origin_id" => null,
                                            "sales_order_id" => $sales_order->id,
                                            "type" => $sales_order->type,
                                            "sales_order_detail_id" => $order_detail->id,
                                            "product_id" => $order_detail->product_id,
                                            "quantity_unit" => $order_detail->quantity,
                                            "fee_percentage" => $position ? $position->fee : null,
                                            "status_fee_id" => $sales_order->status_fee_id,
                                            "status_fee_percentage" => $is_handover->percentage,
                                            "fee_nominal" => 0,
                                            "join_days" => $marketing_join_days,
                                            "confirmed_at" => confirmation_time($sales_order),
                                        ];

                                        $marketing_get_fee_target->push(collect($detail));
                                    }

                                    return $detail;
                                });
                        }

                    });

                    /**
                     * generate fee target sharing
                     */
                    $marketing_get_fee_target->each(function ($marketing) {
                        $fee_target = $this->fee_target_sharing_origin->updateOrCreate([
                            "personel_id" => $marketing["personel_id"],
                            "position_id" => $marketing["position_id"],
                            "sales_order_origin_id" => $marketing["sales_order_origin_id"],
                            "sales_order_id" => $marketing["sales_order_id"],
                            "sales_order_detail_id" => $marketing["sales_order_detail_id"],
                            "product_id" => $marketing["product_id"],
                            "fee_percentage" => $marketing["fee_percentage"],
                            "status_fee_id" => $marketing["status_fee_id"],
                        ], [
                            "status_fee_percentage" => $marketing["status_fee_percentage"],
                            "quantity_unit" => $marketing["quantity_unit"],
                            "fee_nominal" => $marketing["fee_nominal"],
                            "confirmed_at" => $marketing["confirmed_at"],
                        ]);

                        $log = $this->log_fee_target_sharing->updateOrCreate([
                            "sales_order_id" => $marketing["sales_order_id"],
                            "type" => $marketing["type"],
                        ]);
                    });

                    return "fee target sharing generated";
                }

                return "fee target sharing generated, from distributor";
            }

            return "fee target sharing not generated, from follow up";
        }

        return "order does not have confirmation time";
    }
}
