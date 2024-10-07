<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Contest\Entities\ContestParticipant;
use Modules\Distributor\Entities\DistributorContract;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealerV2\Entities\DistributorProductSuspended;
use Modules\SalesOrder\Entities\SalesOrder;

/**
 *
 */
trait DistributorTrait
{
    public function isActiveDistributorContract($distributor_id)
    {
        $distributor_active_now = DistributorContract::query()
            ->where("dealer_id", $distributor_id)
            ->where("contract_start", "<=", now()->format("Y-m-d"))
            ->where("contract_end", ">=", now()->format("Y-m-d"))
            ->first();

        if ($distributor_active_now) {
            return true;
        }
        return false;
    }

    public function isDistributorContractAccordingOrderConfirmationDate($sales_order_id)
    {
        $sales_order = SalesOrder::query()
            ->with([
                "invoice",
            ])
            ->where("type", "2")
            ->whereNotNul("")
            ->where("id", $sales_order_id)
            ->first();
        if ($sales_order) {

        }
    }

    public function subDealerDistributor($store_id, $date = null)
    {
        $sub_dealer_district = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("parent_id", $store_id)
            ->where(function ($QQQ) {
                return $QQQ
                    ->where("type", "sub_dealer")
                    ->orWhere("type", "dealer");
            })
            ->first();

        if (!$sub_dealer_district) {
            $sub_dealer_district = DB::table('address_with_details')->where("parent_id", $store_id)->whereNull("deleted_at")->where("type", "dealer")->first();
        }

        if ($sub_dealer_district) {
            $distributor = DealerV2::query()
                ->whereHas("distributorContract", function ($QQQ) use ($sub_dealer_district, $date) {
                    return $QQQ
                        ->whereHas("area", function ($QQQ) use ($sub_dealer_district) {
                            return $QQQ->where("district_id", $sub_dealer_district->district_id);
                        })
                        ->where("contract_start", "<=", ($date?:now()->format("Y-m-d")))
                        ->where("contract_end", ">=", ($date?:now()->format("Y-m-d")));
                })
                ->get();

            return $distributor->pluck("id");
        }

        return [];
    }

    public function contractContestDistributorSubDealer($store_id, $contest_id, $date = null)
    {
        $distributor_contest_contract = ContestParticipant::query()
            ->where("contest_id", $contest_id)
            ->whereIn("dealer_id", $this->subDealerDistributor($store_id, $date))
            ->get()
            ->pluck("dealer_id");

        return $distributor_contest_contract;
    }

    /**
     * order is inside contract or not
     *
     * @param [type] $sales_order
     * @return boolean
     */
    public function isOrderInsideDistributorContract($sales_order)
    {
        $confirm_date = confirmation_time($sales_order);

        if ($confirm_date) {
            $confirm_date = Carbon::parse(confirmation_time($sales_order))->format("Y-m-d");
        } else {
            return false;
        }

        $order_inside_contract = false;
        if ($sales_order instanceof Invoice) {
            collect($sales_order->salesOrder->dealer->ditributorContract)->each(function ($contract) use ($confirm_date, &$order_inside_contract) {
                if ($confirm_date >= $contract->contract_start && $confirm_date <= $contract->contract_end) {
                    $order_inside_contract = true;
                }
            });
        } else if ($sales_order->dealer) {
            collect($sales_order->dealer->ditributorContract)->each(function ($contract) use ($confirm_date, &$order_inside_contract) {
                if ($confirm_date >= $contract->contract_start && $confirm_date <= $contract->contract_end) {
                    $order_inside_contract = true;
                }
            });
        }

        return $order_inside_contract;
    }

    public function isProductDistributorSuspended($distributor_id, $product_id)
    {
        $product_suspended = DistributorProductSuspended::query()
            ->where("product_id", $product_id)
            ->whereHas("distributorSuspended", function ($QQQ) use ($distributor_id) {
                return $QQQ->where("dealer_id", $distributor_id);
            })
            ->first();

        if ($product_suspended) {
            return true;
        }

        return false;
    }
}
