<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\FeeSharing as FeeSharingPerMarketing;
use Modules\SalesOrder\Events\FeeSharing;

class FeeSharingMarketing
{
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeSharing $event)
    {
        /**
         * fee is for marketing on purchase
         */
        $personel_on_purchase = Personel::query()
            ->with([
                "position" => function ($QQQ) {
                    return $QQQ->with("fee");
                },
            ])
            ->where("id", $event->sales_order->personel_id)
            ->first();

        /* get marketing with all his supervisor to top supervisor */
        $marketing_list = [];
        $marketing_supervisor = $this->parentPersonel($event->sales_order->personel_id);
        $marketing_list = Personel::with("position.fee")->whereNull("deleted_at")->whereIn("id", $marketing_supervisor)->get();

        /* filter supervisor active and Haven't resigned yet */
        $marketing_list = collect($marketing_list)->filter(function ($marketing, $key) {
            return $marketing->status !== "3" && ($marketing->resign_date >= now() || $marketing->resign_date == null);
        });

        $all_spv = $marketing_list;

        /* get store hand over status */
        $store = null;
        if ($event->sales_order->dealer) {
            $store = $event->sales_order->dealer;
        } else {
            $store = $event->sales_order->subDealer;
        }

        /* rm fee percentage */
        $rm_fee_percentage = FeePosition::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->where("fee_as_marketing", "1");
            })
            ->first();

        /* fee position list excludeing purchaser marketing */
        $fee_sharing_data_references = FeePosition::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ;
            })
            ->orderBy("fee")
            ->get();

        $marketing_get_fee = collect();

        /**
         * if sales order is from follow up
         * fee marketing is cut according
         * to the fee on status fee
         * data reference
         */
        if ($event->sales_order->counter_id) {

            /**
             * marketing fee cut according follow up
             */
            $marketing_buyer_join_date_days = 0;
            $marketing_buyer_join_date_days = $personel_on_purchase ? ($personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(now()) : 90) : 0;
            if ($marketing_buyer_join_date_days >= 90 || $store->statusFee->name == "R") {
                $marketing_get_fee->push(collect([
                    "personel_id" => $event->sales_order ? $event->sales_order->personel_id : null,
                    "position_id" => $event->sales_order->personel->position_id,
                    "sales_order_id" => $event->sales_order->id,
                    "fee_percentage" => $store->statusFee->percentage,
                    "fee_target_percentage" => 0,
                    "status_fee" => $store->statusFee->id,
                    "handover_status" => 1,
                    "fee_status" => "marketing follow up fee",
                ]));
            }

            /**
             * sales counter fee
             */
            $marketing_get_fee->push(collect([
                "personel_id" => $event->sales_order ? $event->sales_order->counter_id : null,
                "position_id" => $event->sales_order->salesCounter->position_id,
                "sales_order_id" => $event->sales_order->id,
                "fee_percentage" => $rm_fee_percentage->fee_sc_on_order,
                "fee_target_percentage" => 0,
                "status_fee" => $store->statusFee->id,
                "handover_status" => 0,
                "fee_status" => "sales counter",
            ]));
        }

        /**
         * all marketing position saved even
         * though marketing is not exist,
         * and spv get double fee as
         * purchaser
         */
        collect($fee_sharing_data_references)->map(function ($fee, $key) use ($marketing_get_fee, $marketing_list, $store, $event) {
            $personel_id = collect($marketing_list)
                ->filter(function ($marketing, $key) use ($fee) {
                    return $marketing->position_id == $fee->position_id;
                })
                ->first();

            /* personel purchaser */
            $fee_status = null;
            if ($fee->fee_as_marketing) {
                $personel_id = $event->sales_order->personel;
                $fee_status = "purchaser";
            }

            /* check join date */
            $marketing_buyer_join_date_days = 0;
            $marketing_buyer_join_date_days = $personel_id ? ($personel_id->join_date ? Carbon::parse($personel_id->join_date)->diffInDays(now()) : 90) : 0;
            if ($marketing_buyer_join_date_days < 90 || $store->statusFee->name !== "R") {
                $personel_id = null;
            }

            /* applicator fee */
            if ($fee->is_applicator) {
                $fee_status = "applicator";
            } else if ($fee->is_mm) {
                $fee_status = "marketing manager";
            }

            $marketing_get_fee->push(collect([
                "personel_id" => $personel_id ? $personel_id->id : null,
                "position_id" => $fee->position_id,
                "sales_order_id" => $event->sales_order->id,
                "fee_percentage" => $fee->fee,
                "fee_target_percentage" => $fee->fee,
                "status_fee" => $store->statusFee->id,
                "handover_status" => 0,
                "fee_status" => $fee_status,
            ]));
        });

        /* store data fee sharing */
        foreach ($marketing_get_fee->toArray() as $marketing) {
            $marketing = (object) $marketing;
            FeeSharingPerMarketing::updateOrCreate([
                "personel_id" => $marketing->personel_id,
                "position_id" => $marketing->position_id,
                "sales_order_id" => $marketing->sales_order_id,
                "fee_status" => $marketing->fee_status,
            ], [
                "fee_percentage" => $marketing->fee_percentage,
                "fee_target_percentage" => $marketing->fee_target_percentage,
                "status_fee" => $marketing->status_fee,
                "handover_status" => $marketing->handover_status,
            ]);
        }

        return $marketing_get_fee;
    }
}
