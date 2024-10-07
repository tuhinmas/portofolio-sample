<?php

namespace Modules\SalesOrder\Traits;

use App\Traits\DistributorTrait;
use Modules\Invoice\ClassHelper\PaymentTimeForFee;
use Modules\Personel\Traits\PointMarketingTrait;

/**
 *
 */
trait SalesOrderTrait
{
    use DistributorTrait;
    use PointMarketingTrait;

    public function isSettle($order)
    {
        if ($order->type == "2") {
            return true;
        } elseif ($order->invoice) {
            return $order->invoice->payment_status == "settle" ? true : false;
        }

        return false;
    }

    public function isSettleBeforeMaturity($order, $for_point = false)
    {
        if ($order->type == "2") {
            return true;
        } elseif ($order->invoice) {
            if ($order->invoice->payment_status == "settle") {

                if ($for_point) {
                    return $this->isOrderActivePoint($order);
                } else {
                    if (PaymentTimeForFee::paymentTimeForFeeCalculation($order->invoice) <= maximum_settle_days($order->invoice->created_at->format("Y"))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function filterDirectIndirectDistributorRetailer($sales_orders, $order_type = [1, 2, 3, 4])
    {
        if ($order_type == null) {
            $order_type = [1, 2, 3, 4];
        }
        /* direct to distributor */
        $direct_distributor = $sales_orders
            ->where("type", 1)
            ->filter(function ($order) use ($order_type) {
                if (in_array(1, $order_type)) {
                    if ($this->isOrderInsideDistributorContract($order)) {
                        return $order;
                    }
                }
            });

        /* direct to retailer */
        $direct_retailer = $sales_orders
            ->where("type", 1)
            ->filter(function ($order) use ($order_type) {
                if (in_array(2, $order_type)) {
                    if (!$this->isOrderInsideDistributorContract($order)) {
                        return $order;
                    }
                }
            });

        /* indirect to distributor */
        $indirect_distributor = $sales_orders
            ->where("type", 2)
            ->filter(function ($order) use ($order_type) {
                if (in_array(3, $order_type)) {
                    if ($this->isOrderInsideDistributorContract($order)) {
                        return $order;
                    }
                }
            });

        /* indirect to retailer */
        $indirect_retailer = $sales_orders
            ->where("type", 2)
            ->filter(function ($order) use ($order_type) {
                if (in_array(4, $order_type)) {
                    if (!$this->isOrderInsideDistributorContract($order)) {
                        return $order;
                    }
                }
            });

        $sales_orders = $direct_distributor
            ->concat($direct_retailer)
            ->concat($indirect_distributor)
            ->concat($indirect_retailer)
            ->sortBy(function ($order) {
                if ($order->type == "2") {
                    return $order->date;
                }

                return $order->invoice->created_at;
            });

        return $sales_orders;
    }

    /**
     * total order per year per motnh
     *
     * @param [type] $sales_orders
     * @return void
     */
    public function dataMapTotalPerYearPerMonth($sales_orders)
    {
        return $this->dataMapGroupByYearAndMonth($sales_orders)
            ->map(function ($order_per_year, $year) {
                return collect($order_per_year)->map(function ($order_per_month, $month) {
                    return $this->dataMappingSumAmountOrderPerMonth($order_per_month);
                });
            });
    }

    /**
     * mapping data group by year and month
     * according confirmation time
     */
    public function dataMapGroupByYearAndMonth($sales_orders)
    {
        return $sales_orders
            ->sortBy(function ($order) {
                return confirmation_time($order);
            })
            ->groupBy([
                function ($order) {
                    return confirmation_time($order)->format("Y");
                },
                function ($order) {
                    return confirmation_time($order)->format("M");
                },
            ]);
    }

    /**
     * sum amount according confirmation time
     *
     * @param [type] $sales_orders_month
     * @return void
     */
    public function dataMappingSumAmountOrderPerMonth($sales_orders)
    {
        return collect($sales_orders)->sum(function ($order) {
            if ($order->type == "2") {
                return $order->total;
            }
            return $order->invoice->total;
        });
    }

    public function scopeIndirectSalesBetweenDate($query, $start_date, $end_date = null)
    {
        return $query
            ->where("type", "2")
            // ->whereBetween("date", [$start_date, ($end_date ? $end_date : now()->format("Y-m-d"))]);
            ->where('date', ">=", $start_date)
            ->where('date', "<=", ($end_date? $end_date: now()->format("Y-m-d")));
    }
}
