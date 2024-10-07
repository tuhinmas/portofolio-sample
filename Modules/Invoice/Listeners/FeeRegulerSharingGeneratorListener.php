<?php

namespace Modules\Invoice\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\FeeSharing;

class FeeRegulerSharingGeneratorListener
{
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Personel $personel, FeePosition $fee_position)
    {
        $this->personel = $personel;
        $this->fee_position = $fee_position;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeMarketingEvent $event)
    {
        /*
        |--------------------------------------------------------------------------
        | Generate fee reguler sharing to all marketing according supervising
        |--------------------------------------------------------------------------
         *
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
            ->where("id", $event->invoice->salesOrder->personel_id)
            ->first();

        /**
         * get marketing with all his supervisor to top supervisor
         */
        $marketing_list = [];
        $marketing_supervisor = $this->parentPersonel($event->invoice->salesOrder->personel_id);
        $marketing_list = $this->personel->with("position.fee")->whereNull("deleted_at")->whereIn("id", $marketing_supervisor)->get();

        /**
         * filter supervisor active and haven't resigned yet
         */
        $marketing_list = collect($marketing_list)->filter(function ($marketing, $key) {
            return $marketing->status !== "3" && ($marketing->resign_date >= now() || $marketing->resign_date == null);
        });

        $all_spv = $marketing_list;

        /* get status fee hand over status */
        $status_fee = $event->invoice->salesOrder->statusFee;

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
                $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(now()) : 0;
            }
        }

        $marketing_get_fee = collect([]);
                
        /**
         * if sales order is from follow up
         * fee marketing is cut according
         * to the fee on status fee
         * data reference
         */
        if ($event->invoice->salesOrder->counter_id != null) {

            /**
             * sales counter fee
             */
            $marketing_get_fee->push(collect([
                "personel_id" => $event->invoice->salesOrder ? $event->invoice->salesOrder->counter_id : null,
                "position_id" => $event->invoice->salesOrder->salesCounter->position_id,
                "sales_order_id" => $event->invoice->salesOrder->id,
                "fee_percentage" => $rm_fee_percentage->fee_sc_on_order,
                "fee_target_percentage" => 0,
                "status_fee" => $status_fee->id,
                "handover_status" => 0,
                "fee_status" => "sales counter",
                "confirmed_at" => $event->invoice->created_at,
            ]));
        }

        /**
         * if marketing purchaser join date more than 90 days and store is not reguler
         * (handover dealer from another marketing)
         */
        if ($marketing_buyer_join_date_days >= 90 && $status_fee->name != "R") {
            $marketing_position = collect($fee_sharing_data_references)->where("position_id", $event->invoice->salesOrder->personel->position_id)->first();

            $marketing_get_fee->push(collect([
                "personel_id" => $event->invoice->salesOrder ? $event->invoice->salesOrder->personel_id : null,
                "position_id" => $rm_fee_percentage->position_id,
                "sales_order_id" => $event->invoice->salesOrder->id,
                "fee_percentage" => $status_fee->percentage,
                "fee_target_percentage" => 0,
                "status_fee" => $status_fee->id,
                "handover_status" => 1,
                "fee_status" => "marketing fee hand over",
                "confirmed_at" => $event->invoice->created_at,
            ]));
        }

        /**
         * all marketing position saved even
         * though marketing is not exist,
         * and spv get double fee as
         * purchaser
         */
        collect($fee_sharing_data_references)->map(function ($fee, $key) use ($marketing_get_fee, $marketing_list, $event, $status_fee, $marketing_buyer_join_date_days) {
            $personel = collect($marketing_list)
                ->filter(function ($marketing, $key) use ($fee) {
                    return $marketing->position_id == $fee->position_id;
                })
                ->first();

            /* personel purchaser */
            $fee_status = null;
            if ($fee->fee_as_marketing == 1) {
                $personel = $event->invoice->salesOrder->personel;
                $fee_status = "purchaser";

                /**
                 * check purchaser join date, only in marketing
                 * or iclude sc must check?
                 */
                // $marketing_buyer_join_date_days = 0;
                // $marketing_buyer_join_date_days = $personel_id ? ($personel_id->join_date ? Carbon::parse($personel_id->join_date)->diffInDays(now()) : 90) : 0;

                $marketing_join_days = $personel_id ? ($personel_id->join_date ? Carbon::parse($personel_id->join_date)->diffInDays(now()) : 0) : 0;

                if ($marketing_join_days < 90 && $status_fee->name !== "R") {
                    $personel = null;
                }
            }

            /* applicator fee */
            if ($fee->is_applicator != null) {
                $fee_status = "applicator";
            } else if ($fee->is_mm != null) {
                $fee_status = "marketing manager";
            }

            $marketing_get_fee->push(collect([
                "personel_id" => $personel ? $personel->id : null,
                "position_id" => $fee->position_id,
                "sales_order_id" => $event->invoice->salesOrder->id,
                "fee_percentage" => $fee->fee,
                "fee_target_percentage" => $fee->fee,
                "status_fee" => $status_fee->id,
                "handover_status" => 0,
                "fee_status" => $fee_status,
                "confirmed_at" => $event->invoice->created_at,
            ]));
        });

        /* store data fee sharing */
        foreach ($marketing_get_fee->toArray() as $marketing) {
            $marketing = (object) $marketing;
            $log = FeeSharing::updateOrCreate([
                "personel_id" => $marketing->personel_id,
                "position_id" => $marketing->position_id,
                "sales_order_id" => $marketing->sales_order_id,
                "fee_status" => $marketing->fee_status,
                "fee_percentage" => $marketing->fee_percentage,
            ], [
                "fee_target_percentage" => $marketing->fee_target_percentage,
                "status_fee" => $marketing->status_fee,
                "handover_status" => $marketing->handover_status,
                "confirmed_at" => $marketing->confirmed_at,
            ]);
        }
        return "fee sharing origin generated";
    }
}
