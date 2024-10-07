<?php

namespace Modules\SalesOrder\Traits;

use App\Traits\DistributorTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Modules\SalesOrder\Traits\SalesOrderTrait;

/**
 *
 */
trait ScopeSalesOrder
{
    use DistributorTrait;
    use SalesOrderTrait;

    /**
     * sales order status considered for fee marketing
     *
     * @return self
     */
    public function scopeConsiderOrderStatusForFeeMarketing($query)
    {
        return $query
            ->whereIn("status", ["confirmed", "pending"])
            ->whereNull("afftected_by_return")
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            });
    }

    public function scopeYearOfNota($query, $year)
    {
        return $query->whereYear("date", $year);
    }

    public function scopeQuarterOfNota($query, $quarter)
    {
        return $query->whereRaw("quarter(date) = ?", $quarter);
    }

    public function scopeConsideredOrder($query)
    {
        return $query->whereIn("status", ["confirmed", "pending", "returned"]);
    }

    public function scopeConsideredOrderAsPoint($query)
    {
        return $query->whereIn("status", ["confirmed", "pending", "returned"]);
    }

    public function scopeConsideredOrderFromYear($query, $year, $dealer_type = null)
    {
        $table_name = self::getTable();
        return $query
            ->considerOrderStatusForRecap()
            ->where(function ($QQQ) use ($table_name, $year) {
                return $QQQ
                    ->where(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "2")
                            ->whereYear("{$table_name}.date", ">", $year);
                    })
                    ->orWhere(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                return $QQQ->whereYear("created_at", ">", $year);
                            });
                    });
            });
    }

    public function scopeConsideredOrderToYear($query, $year)
    {
        $table_name = self::getTable();
        return $query
            ->considerOrderStatusForRecap()
            ->where(function ($QQQ) use ($table_name, $year) {
                return $QQQ
                    ->where(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "2")
                            ->whereYear("{$table_name}.date", "<=", $year);
                    })
                    ->orWhere(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                return $QQQ->whereYear("created_at", "<=", $year);
                            });
                    });
            });
    }

    /**
     * sales order for fee marketing active
     *
     * @param [type] $query
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function scopeFeeMarketingActive($query, $year, $quarter)
    {
        return $query->where(function ($QQQ) use ($year, $quarter) {
            return $QQQ
                ->considerOrderStatusForFeeMarketing()
                ->where(function ($QQQ) use ($year, $quarter) {
                    return $QQQ
                        ->where(function ($QQQ) use ($year, $quarter) {
                            return $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($year, $quarter) {
                                    return $QQQ
                                        ->where("payment_status", "settle")
                                        ->whereYear("created_at", $year)
                                        ->whereRaw("quarter(created_at) = ?", $quarter);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($year, $quarter) {
                            return $QQQ
                                ->where("type", "2")
                                ->whereYear("date", $year)
                                ->whereRaw("quarter(date) = ?", $quarter);
                        });
                });
        });
    }

    /**
     * confirmed order means order has confimr by support
     * status is confirmed, pending
     *
     * @param [type] $query
     * @return void
     */
    public function scopeConfirmedOrder($query)
    {
        $table_name = self::getTable();
        return $query
            ->considerOrderStatusForFeeMarketing()
            ->where(function ($QQQ) use ($table_name) {
                return $QQQ
                    ->where(function ($QQQ) use ($table_name) {
                        return $QQQ->where("{$table_name}.type", "2");
                    })
                    ->orWhere(function ($QQQ) use ($table_name) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->whereHas("invoice");
                    });
            });
    }

    /**
     * consider order for recap
     *
     * @param [type] $query
     * @param [type] $year
     * @return void
     */
    public function scopeConsideredOrderByYear($query, $year)
    {
        $table_name = self::getTable();
        return $query
            ->considerOrderStatusForRecap()
            ->where(function ($QQQ) use ($table_name, $year) {
                return $QQQ
                    ->where(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "2")
                            ->whereYear("{$table_name}.date", $year);
                    })
                    ->orWhere(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                return $QQQ->whereYear("created_at", $year);
                            });
                    });
            });
    }

    public function scopeQuartalOrder($query, $year, $quartal, $dealer_type = null)
    {
        $date_start = null;
        $date_end = null;
        for ($i = 0; $i < 4; $i++) {
            if (($i + 1) == $quartal) {
                $date_start = CarbonImmutable::parse($year . "-01-01")->addQuarter($i);
                $date_end = $date_start->endOfQuarter();
            }
        }

        $table_name = self::getTable();
        return $query
            ->considerOrderStatusForRecap()
            ->where(function ($QQQ) use ($year, $quartal, $table_name, $date_start, $date_end) {
                return $QQQ
                    ->where(function ($QQQ) use ($year, $quartal, $table_name, $date_start, $date_end) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($year, $quartal, $table_name, $date_start, $date_end) {
                                return $QQQ
                                    ->whereYear("created_at", $year)
                                    ->whereRaw("quarter(created_at) = ?", $quartal);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($year, $quartal, $table_name, $date_start, $date_end) {
                        return $QQQ
                            ->where("{$table_name}.type", "2")
                            ->whereYear("{$table_name}.date", $year)
                            ->whereRaw("quarter({$table_name}.date) = ?", $quartal);
                    });
            });
    }

    public function scopeUnconfirmedOrUnSubmitedOrderQuartal($query, $year, $quartal)
    {
        $table_name = self::getTable();
        return $query
            ->whereYear("{$table_name}.created_at", $year)
            ->whereRaw("quarter({$table_name}.created_at) = ?", $quartal)
            ->where(function ($QQQ) use ($year, $quartal, $table_name) {
                return $QQQ
                    ->where(function ($QQQ) use ($year, $quartal, $table_name) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->whereDoesntHave("invoice");
                    })
                    ->orWhere(function ($QQQ) use ($year, $quartal, $table_name) {
                        return $QQQ
                            ->where("{$table_name}.type", "2")
                            ->whereNull("{$table_name}.date");
                    });
            });
    }

    /*
    |-------------------------------------
    | POINT MARKETING
    |--------------------------
     */

    public function scopeConsiderOrderStatusForPointMarketing($query)
    {
        return $query->whereIn("status", ["confirmed", "pending", "returned"]);
    }

    public function scopePointMarketingPerProduct($query, $personel_id, $year)
    {
        return $query
            ->where("personel_id", $personel_id)
            ->considerOrderStatusForPointMarketing()
            ->pointMarketingByYear($year);
    }

    /**
     * point marketing total by marketing and year
     *
     * @param [type] $query
     * @param [type] $personel_id
     * @param [type] $year
     * @return void
     */
    public function scopePointMarketingTotal($query, $personel_id, $year)
    {
        return $query
            ->where("personel_id", $personel_id)
            ->pointMarketingByYear($year);
    }

    /**
     * point marketing total by marketing and year
     *
     * @param [type] $query
     * @param [type] $personel_id
     * @param [type] $year
     * @return void
     */
    public function scopePointMarketingByYear($query, $year)
    {
        return $query
            ->considerOrderStatusForPointMarketing()
            ->whereHas("logWorkerSalesPoint")
            ->salesByYear($year);
    }

    /**
     * point marketing total by marketing and year
     *
     * @param [type] $query
     * @param [type] $personel_id
     * @param [type] $year
     * @return void
     */
    public function scopeSalesByYear($query, $year)
    {
        return $query
            ->where(function ($QQQ) use ($year) {
                return $QQQ
                    ->where(function ($QQQ) use ($year) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                return $QQQ->yearOfProforma($year);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($year) {
                        return $QQQ
                            ->where("type", "2")
                            ->yearOfNota($year);
                    });
            });
    }

    public function scopeSalesByQuarter($query, $quarter)
    {
        return $query
            ->where(function ($QQQ) use ($quarter) {
                return $QQQ
                    ->where(function ($QQQ) use ($quarter) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($quarter) {
                                return $QQQ->quarterOfProforma($quarter);

                            });
                    })
                    ->orWhere(function ($QQQ) use ($quarter) {
                        return $QQQ
                            ->where("type", "2")
                            ->quarterOfNota($quarter);
                    });
            });
    }

    public function scopeMarketingSalesByYear($query, $personel_id, $year)
    {
        return $query
            ->where("personel_id", $personel_id)
            ->salesByYear($year);
    }

    public function scopeConsideredMarketingSalesByYear($query, $personel_id, $year)
    {
        return $query
            ->marketingSalesByYear($personel_id, $year)
            ->consideredOrder();
    }

    public function scopeConsideredMarketingSalesByQuarter($query, $personel_id, $year, $quarter)
    {
        return $query
            ->consideredMarketingSalesByYear($personel_id, $year)
            ->salesByQuarter($quarter);
    }

    /**
     * point marketing total by marketing and year
     *
     * @param [type] $query
     * @param [type] $personel_id
     * @param [type] $year
     * @return void
     */
    public function scopePointMarketingActive($query, $personel_id, $year)
    {
        return $query
            ->where("personel_id", $personel_id)
            ->pointMarketingByYear($year)
            ->settleOrder();
    }

    /**
     * considered settle order
     *
     * @param [type] $query
     * @return void
     */
    public function scopeSettleOrder($query)
    {
        return $query
            ->considerOrderStatusForPointMarketing()
            ->where(function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) {
                                return $QQQ->where("payment_status", "settle");
                            });
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ->where("type", "2");
                    });
            });
    }

    /*
    |------------------------
    | RECAP
    |------------
     */
    public function scopeConsiderOrderStatusForRecap($query)
    {
        return $query->whereIn("sales_orders.status", ["confirmed", "pending", "returned"]);
    }

    public function scopeSalesOrderBetweenToDate($query, $start_date, $end_date)
    {
        return $query
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ->whereBetween("created_at", [$start_date, $end_date]);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "2")
                            ->whereBetween("date", [$start_date, $end_date]);
                    });
            });
    }

    public function scopeBySettle($query, $payment_status = [1, 2])
    {
        return $query
            ->when(in_array(1, $payment_status) && count($payment_status) == 1, function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($QQQ) {
                                        return $QQQ->where("payment_status", "settle");
                                    });
                            })
                            ->orWhere("type", "2");
                    });
            })
            ->when(in_array(2, $payment_status) && count($payment_status) == 1, function ($QQQ) {
                return $QQQ
                    ->where("type", "1")
                    ->whereHas("invoice", function ($QQQ) {
                        return $QQQ->where("payment_status", "!=", "settle");
                    });
            })
            ->when(in_array(1, $payment_status) && in_array(2, $payment_status) && count($payment_status) == 2, function ($QQQ) {
                return $QQQ;
            });
    }

    /**
     * dealer has contract in thenlast four quarter
     *
     * @param [type] $query
     * @param [type] $quarter_first
     * @return void
     */
    public function scopeDistributorLastFourQuarter($query, $date_start, $date_end = null)
    {
        return $query->whereHas("dealer", function ($QQQ) use ($date_start, $date_end) {
            return $QQQ->distributorLastFourQuarter($date_start, $date_end);
        });
    }

    public function scopeSalesToDistributorBetweenToDate($query, $start_date, $end_date, $dealer_type = null)
    {
        if ($dealer_type) {
            $sales_order = self::query()
                ->with(
                    [
                        "invoice",
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        },
                    ],
                )
                ->considerOrderStatusForRecap()
                ->salesOrderBetweenToDate($start_date, $end_date)
                ->get()
                ->filter(function ($order) use ($dealer_type) {

                    /* check order is inside contract */
                    if ($dealer_type == "distributor") {
                        if ($this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else if ($dealer_type == "retailer") {
                        if (!$this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else {
                        return $order;
                    }
                })
                ->pluck("id");
            return $query->whereIn("id", $sales_order);
        } else {
            return $query;
        }
    }

    public function scopeToDistributorOrRetailer($query, $dealer_type = "distributor", $date_start, $date_end = null)
    {
        return $query

        /**
         * filter distributor
         * distributor is dealer have a contract bigger then date
         */
            ->when($dealer_type == "distributor", function ($QQQ) use ($date_start, $date_end) {
                return $QQQ->distributorLastFourQuarter($date_start, $date_end);
            })

            /**
         * filter retailer
         * retailer is dealer does not have active contract now or
         * dealer does not have any contract in the last four quarters
         */
            ->when($dealer_type == "retailer", function ($QQQ) use ($date_start, $date_end) {
                return $QQQ->retailerLastFourQuarter($date_start, $date_end);
            });
    }

    public function scopeRetailerLastFourQuarter($query, $date_start, $date_end = null)
    {
        return $query->where(function ($QQQ) use ($date_start, $date_end) {
            return $QQQ
                ->whereHas("dealer", function ($QQQ) use ($date_start, $date_end) {
                    return $QQQ->retailerLastFourQuarter($date_start);
                })
                ->orWhereHas("subDealer");
        });
    }

    /**
     * list order per month
     *
     * @param [type] $query
     * @param [type] $year
     * @param [type] $month
     * @return void
     */
    public function scopeConsideredOrderPerMonth($query, $year, $month)
    {
        return $query->where(function ($QQQ) use ($year, $month) {
            return $QQQ
                ->considerOrderStatusForRecap()
                ->where(function ($QQQ) use ($year, $month) {
                    return $QQQ
                        ->where(function ($QQQ) use ($year, $month) {
                            return $QQQ
                                ->where("type", "2")
                                ->whereYear("date", "=", $year)
                                ->whereMonth("date", "=", $month);
                        })
                        ->orWhere(function ($QQQ) use ($year, $month) {
                            return $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($year, $month) {
                                    return $QQQ->whereYear("created_at", $year)
                                        ->whereMonth("created_at", $month);
                                });
                        });
                });
        });
    }

    public function scopeConsideredStatusConfirmedReturnedPending($query, $quarter_first)
    {
        return $query
            ->considerOrderStatusForRecap()
            ->where(function ($QQQ) use ($quarter_first) {
                return $QQQ
                    ->where(function ($QQQ) use ($quarter_first) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($quarter_first) {
                                return $QQQ->where("created_at", ">=", $quarter_first);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($quarter_first) {
                        return $QQQ
                            ->where("type", "2")
                            ->where("date", ">=", $quarter_first);
                    });
            });
    }

    public function scopeIndirectListPerDealerPerQuartal($query, $quartal, $year, $store_id, $personel_id = null, $dealer_type = null)
    {
        $indirects = [];
        if ($dealer_type) {
            $indirects = self::query()
                ->with([
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])
                ->indirectInQuarter($quartal, $year, $store_id, $personel_id)
                ->get()
                ->filter(function ($invoice) use ($dealer_type) {

                    /* check order is inside contract */
                    if ($dealer_type == "distributor") {
                        if ($this->isOrderInsideDistributorContract($invoice)) {
                            return $invoice;
                        }
                    } else if ($dealer_type == "retailer") {
                        if (!$this->isOrderInsideDistributorContract($invoice)) {
                            return $invoice;
                        }
                    } else {
                        return $invoice;
                    }
                })
                ->pluck("id");
        }

        return $query
            ->indirectInQuarter($quartal, $year, $store_id, $personel_id)
            ->when($dealer_type, function ($QQQ) use ($indirects) {
                return $QQQ->whereIn("id", $indirects);
            });
    }

    public function scopeIndirectInQuarter($query, $quartal, $year, $store_id, $personel_id = null)
    {
        return $query
            ->whereRaw("quarter(date) = " . $quartal)
            ->whereYear("date", $year)
            ->considerOrderStatusForRecap()
            ->where("store_id", $store_id)
            ->where("type", "2")
            ->when($personel_id, function ($QQQ) use ($personel_id) {
                return $QQQ->where("personel_id", $personel_id);
            });
    }

    public function scopeFilterDirectIndirectDistributorRetailer($query, $order_type = null)
    {
        return $query
            ->when(collect($order_type)->contains(fn($type) => $type <= 2) && !collect($order_type)->contains(fn($type) => $type >= 3), function ($QQQ) {
                return $QQQ->where("type", 1);
            })
            ->when(collect($order_type)->contains(fn($type) => $type >= 3) && !collect($order_type)->contains(fn($type) => $type <= 2), function ($QQQ) {
                return $QQQ->where("type", 2);
            })
            ->when(collect($order_type)->contains(fn($type) => $type >= 1 && $type <= 4), function ($QQQ) {
                return $QQQ->whereIn("type", [1, 2]);
            })
            ->when(empty($order_type), function ($QQQ) {
                return $QQQ;
            });
    }

    public function scopeIsOffice($query, $status = true)
    {
        return $query->where("is_office", $status);
    }

    /*
    |--------------------
    | Return
    |----------------
     */
    public function scopeConsideredOrderForReturn($query)
    {
        return $query->whereIn("status", ["draft", "submited", "reviewed", "confirmed", "pending", "returned", "onhold"]);
    }

    public function scopeReturnedOrderInQuarterByDate($query, $date = null)
    {
        return $query
            ->where("status", "returned")
            ->quarterOrderByDate($date);
    }

    public function scopeQuarterOrderByDate($query, $date)
    {
        return $query
            ->where(function ($QQQ) use ($date) {
                return $QQQ
                    ->where(function ($QQQ) use ($date) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($date) {
                                return $QQQ
                                    ->yearOfProforma(Carbon::parse($date)->year)
                                    ->quarterOfProforma(Carbon::parse($date)->quarter);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($date) {
                        return $QQQ
                            ->where("type", "2")
                            ->yearOfNota(Carbon::parse($date)->year)
                            ->quarterOfNota(Carbon::parse($date)->quarter);
                    });
            });
    }

    /**
     * MARKETING CHANGES
     */
    public function scopeConsideredOrderOnMarketingChange($query)
    {
        return $query->whereIn("status", ["draft", "reviewed", "submited"]);
    }
}
