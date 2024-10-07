<?php

namespace Modules\SalesOrder\Traits;

use App\Traits\DistributorTrait;
use Modules\SalesOrder\Traits\SalesOrderTrait;

trait ScopeSalesOrderDistributor
{
    use DistributorTrait;
    use SalesOrderTrait;

    /**
     * -------------------------
     * DISTRIBUOR PICKUP
     * --------------------
     */
    public function scopeConsideredStatusDistributorSalesPending($query)
    {
        return $query->whereIn("status", ["pending", "onhold"]);
    }

    public function scopeConsideredPickupOrder($query)
    {
        return $query->whereIn("status", ["confirmed", "pending"]);
    }

    public function scopeDistributorSalesPendingDuringContract($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->consideredStatusDistributorSalesPending()
            ->where("distributor_id", $distributor_id)
            ->where("type", "2")
            ->whereNotNull("date")
            ->whereDate("date", ">=", $start_date)
            ->whereDate("date", "<=", $end_date ? $end_date : now()->format("Y-m-d"));
    }

    public function scopeDistributorPickUpDuringContract($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->considerOrderStatusForRecap()
            ->where("store_id", $distributor_id)
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ

                /* direct sales */
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ
                                    ->whereDate("created_at", ">=", $start_date)
                                    ->whereDate("created_at", "<=", $end_date ? $end_date : now()->format("Y-m-d"));

                            });
                    })

                    /* order (direct or indirect) that return by distributor during contract */
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("status", "returned")
                            ->whereNotNull("return")
                            ->whereDate("return", ">=", $start_date)
                            ->whereDate("return", "<=", ($end_date ? $end_date : now()->format("Y-m-d")));
                    })

                    /* indirect nota match with contract*/
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "2")
                            ->whereDate("date", ">=", $start_date)
                            ->whereDate("date", "<=", $end_date ? $end_date : now()->format("Y-m-d"));
                    });
            });
    }

    public function scopeDistributorPickUpDuringContractAccordingPickupDate($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->consideredOrder()
            ->where("store_id", $distributor_id)
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ
                                    ->proformaAccordingDate(">=", $start_date)
                                    ->proformaAccordingDate("<=", $end_date ? $end_date : now()->format("Y-m-d"));
                            });
                    })
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "2")
                            ->indirectAccordingDate(">=", $start_date)
                            ->indirectAccordingDate("<=", $end_date ? $end_date : now()->format("Y-m-d"));
                    });
            });

    }

    public function scopeDistributorPickUpDuringContractExcludeReturn($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->consideredPickupOrder()
            ->where("store_id", $distributor_id)
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ
                                    ->proformaAccordingDate(">=", $start_date)
                                    ->proformaAccordingDate("<=", $end_date ? $end_date : now()->format("Y-m-d"));
                            });
                    })
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "2")
                            ->indirectAccordingDate(">=", $start_date)
                            ->indirectAccordingDate("<=", $end_date ? $end_date : now()->format("Y-m-d"));
                    });
            });

    }

    /**
     * piuckup disrtibutor consider return
     *
     * @param [type] $query
     * @param [type] $distributor_id
     * @param [type] $start_date
     * @param [type] $end_date
     * @return void
     */
    public function scopeDistributorPickUpReturnDuringContract($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->whereHas("salesOrderDetail", function ($QQQ) {
                return $QQQ->whereNotNull("returned_quantity");
            })
            ->where("store_id", $distributor_id)
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ

                /* direct has credit memo during contract, no matter status is */
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ->whereHas("creditMemos", function ($QQQ) use ($start_date, $end_date) {
                                    return $QQQ
                                        ->where("date", ">=", $start_date)
                                        ->where("date", "<=", ($end_date ? $end_date : now()->format("Y-m-d")));
                                });
                            });
                    })

                    /* direct status returned but doesn't have credit memo destination, to handle existing data */
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("type", "1")
                            ->where("status", "returned")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ
                                    ->proformaAccordingDate(">=", $start_date)
                                    ->proformaAccordingDate("<=", $end_date)
                                    ->whereDoesntHave("creditMemoDestination");
                            });
                    })

                    /* indirect to distributor and return during contract */
                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->where("status", "returned")
                            ->where("type", "2")
                            ->whereDate("return", ">=", $start_date)
                            ->whereDate("return", "<=", ($end_date ? $end_date : now()->format("Y-m-d")));
                    });
            });

    }

    /**
     * DISTRIBUOR SALES
     * retrun order consider as stok according return date not nota date
     * if retrun date higher than last opname date
     */
    public function scopeDistributorSalesDuringContractBydate($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->where("distributor_id", $distributor_id)
            ->where("type", "2")
            ->whereNotNull("date")
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->whereDate("date", ">=", $start_date)
                            ->whereDate("date", "<=", ($end_date ? $end_date : now()->format("Y-m-d")));
                    });

                // ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                //     return $QQQ
                //         ->where("status", "returned")
                //         ->whereNotNull("return")
                //         ->whereDate("return", ">=", $start_date)
                //         ->whereDate("return", "<=", ($end_date ? $end_date : now()->format("Y-m-d")));
                // });
            });
    }

    public function scopeDistributorSalesDuringContractByNotaDate($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->consideredOrder()
            ->where("distributor_id", $distributor_id)
            ->where("type", "2")
            ->whereNotNull("date")
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->whereDate("date", ">=", $start_date)
                            ->whereDate("date", "<=", ($end_date ? $end_date : now()->format("Y-m-d")));
                    });
            });
    }

    public function scopeDistributorSalesDuringContractBydateExcludeReturn($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->where("distributor_id", $distributor_id)
            ->where("type", "2")
            ->whereNotNull("date")
            ->where(function ($QQQ) use ($start_date, $end_date) {
                return $QQQ
                    ->where(function ($QQQ) use ($start_date, $end_date) {
                        return $QQQ
                            ->indirectAccordingDate(">=", $start_date)
                            ->indirectAccordingDate("<=", $end_date ? $end_date : now()->format("Y-m-d"));
                    });
            });
    }

    public function scopeDistributorSalesDuringContract($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->consideredOrder()
            ->distributorSalesDuringContractBydate($distributor_id, $start_date, $end_date);
    }

    public function scopeDistributorSalesDuringContractExcludeReturn($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->consideredOrder()
            ->distributorSalesDuringContractBydateExcludeReturn($distributor_id, $start_date, $end_date);
    }

    public function scopeDistributorSalesReturnDuringContract($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->where("status", "returned")
            ->whereNotNull("return")
            ->where("distributor_id", $distributor_id)
            ->whereDate("return", ">=", $start_date)
            ->whereDate("return", "<=", $end_date ? $end_date : now()->format("Y-m-d"));
    }

    public function scopeDistributorSubmitedSalesDuringContract($query, $distributor_id, $start_date, $end_date = null)
    {
        return $query
            ->whereIn("status", ["submited", "onhold"])
            ->distributorSalesDuringContractBydate($distributor_id, $start_date, $end_date);
    }

    public function scopeFilterDirectIndirectDistributorRetailerInYear($query, $year, $month = null, $dealer_id = null, $personel_id = null, $order_type = [1, 2, 3, 4])
    {
        $sales_orders = self::query()
            ->with([
                "invoice",
                "dealer" => function ($QQQ) {
                    return $QQQ->with([
                        "distributorContract",
                        "ditributorContract",
                    ]);
                },
            ])
            ->when($month, function ($QQQ) use ($year, $month) {
                return $QQQ->consideredOrderPerMonth($year, $month);
            })
            ->filterDirectIndirectDistributorRetailer($order_type)
            ->when($dealer_id, function ($QQQ) use ($dealer_id) {
                return $QQQ->where("store_id", $dealer_id);
            })
            ->when($personel_id, function ($QQQ) use ($personel_id) {
                return $QQQ->where("personel_id", $personel_id);
            })
            ->consideredOrderByYear($year)
            ->get();

        $sales_orders = $this->filterDirectIndirectDistributorRetailer($sales_orders, $order_type)
            ->pluck("id")
            ->unique()
            ->toArray();

        return $query->whereIn("sales_orders.id", $sales_orders);
    }
}
