<?php

namespace Modules\Invoice\Listeners;

use Carbon\Carbon;
use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;

class FeeRegulerSharingOriginGeneratorListener
{
    use FeeMarketingTrait;
    use DistributorStock;
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        SalesOrder $sales_order,
        Personel $personel,
        FeePosition $fee_position
    ) {
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->sales_order_detail = $sales_order_detail;
        $this->sales_order = $sales_order;
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

        /**
         * genarate fee sharing origin
         */
        return $this->feeSharingOriginGenerator($event->invoice->salesOrder);

        /**
         * PENDING
         *
         * follow up order will get fee if follow up days
         * more than 60 days
         */
        $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

        if ($event->invoice->salesOrder->counter_id && $event->invoice->salesOrder->follow_up_days <= ($follow_up_days_reference ? $follow_up_days_reference->follow_up_days : 60)) {
            return "this order is follow up and less than " . ($follow_up_days_reference ? $follow_up_days_reference->follow_up_days : 60) . " days, this order will not get fee at all";
        }

        $active_contract = $this->distributorActiveContract($event->invoice->salesOrder->store_id);

        if (!$active_contract) {

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

            /* get marketing with all his supervisor to top supervisor */
            $marketing_list = [];
            $marketing_supervisor = $this->parentPersonel($event->invoice->salesOrder->personel_id);
            $marketing_list = $this->personel->query()
                ->with("position.fee")
                ->whereNull("deleted_at")
                ->whereIn("id", $marketing_supervisor)
                ->get();

            /* filter supervisor active and Haven't resigned yet */
            $marketing_list = collect($marketing_list)->filter(function ($marketing, $key) use ($event) {
                return $marketing->status !== "3" && ($marketing->resign_date >= ($event->invoice->salesOrder->type == "2" ? $event->invoice->salesOrder->created_at : $event->invoice->created_at) || $marketing->resign_date == null);
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
                    $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(($event->invoice->salesOrder->type == "2" ? $event->invoice->salesOrder->created_at : $event->invoice->created_at), false) : 0;
                }
            }

            /**
             * generete fee sharing per product
             */
            collect($event->invoice->salesOrder->sales_order_detail)->each(function ($order_detail) use ($event, $rm_fee_percentage, $status_fee, $marketing_buyer_join_date_days, $fee_sharing_data_references, &$marketing_get_fee, $marketing_list, $personel_on_purchase) {

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
                        "sales_order_origin_id" => null,
                        "sales_order_id" => $event->invoice->salesOrder->id,
                        "sales_order_detail_id" => $order_detail->id,
                        "fee_percentage" => $rm_fee_percentage->fee_sc_on_order,
                        "status_fee" => $status_fee->id,
                        "handover_status" => 0,
                        "fee_status" => "sales counter",
                        "marketing_join_days" => 0,
                        "confirmed_at" => confirmation_time($event->invoice->salesOrder),
                    ]));
                }

                /**
                 * marketing fee if order is from
                 * handover store
                 */
                if ($marketing_buyer_join_date_days >= 90 && $status_fee->name != "R") {

                    $marketing_get_fee->push(collect([
                        "personel_id" => $event->invoice->salesOrder ? $event->invoice->salesOrder->personel_id : null,
                        "position_id" => $rm_fee_percentage->position_id,
                        "sales_order_origin_id" => null,
                        "sales_order_id" => $event->invoice->salesOrder->id,
                        "sales_order_detail_id" => $order_detail->id,
                        "fee_percentage" => $status_fee->percentage,
                        "status_fee" => $status_fee->id,
                        "handover_status" => 1,
                        "fee_status" => "marketing fee hand over",
                        "marketing_join_days" => $marketing_buyer_join_date_days,
                        "confirmed_at" => confirmation_time($event->invoice->salesOrder),
                    ]));
                }

                /**
                 * all marketing position saved even
                 * though marketing is not exist,
                 * and spv get double fee as
                 * purchaser
                 */
                collect($fee_sharing_data_references)->map(function ($fee, $key) use ($marketing_get_fee, $marketing_list, $status_fee, $order_detail, $personel_on_purchase, $event) {
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
                    $marketing_join_days = $personel_id ? ($personel_id->join_date ? Carbon::parse($personel_id->join_date)->diffInDays(($event->invoice->salesOrder->type == "2" ? $event->invoice->salesOrder->created_at : $event->invoice->created_at), false) : 0) : 0;

                    if ($marketing_join_days < 90 && $status_fee->name !== "R") {
                        $personel_id = null;
                    }

                    /**
                     * sales counter fee
                     */
                    $marketing_get_fee->push(collect([
                        "personel_id" => $personel_id ? $personel_id->id : null,
                        "position_id" => $fee->position_id,
                        "sales_order_origin_id" => null,
                        "sales_order_id" => $event->invoice->salesOrder->id,
                        "sales_order_detail_id" => $order_detail->id,
                        "fee_percentage" => $fee->fee,
                        "status_fee" => $status_fee->id,
                        "status_fee_name" => $status_fee->name,
                        "handover_status" => 0,
                        "fee_status" => $fee_status,
                        "marketing_join_days" => $marketing_join_days,
                        "confirmed_at" => confirmation_time($event->invoice->salesOrder),
                    ]));

                });
            });

            /* store data fee sharing */
            foreach ($marketing_get_fee->toArray() as $marketing) {
                $marketing = (object) $marketing;
                $log = $this->fee_sharing_origin->updateOrCreate([
                    "personel_id" => $marketing->personel_id,
                    "position_id" => $marketing->position_id,
                    "sales_order_origin_id" => $marketing->sales_order_origin_id,
                    "sales_order_id" => $marketing->sales_order_id,
                    "sales_order_detail_id" => $marketing->sales_order_detail_id,
                    "fee_status" => $marketing->fee_status,
                    "fee_percentage" => $marketing->fee_percentage,
                    "status_fee" => $marketing->status_fee,
                    "handover_status" => $marketing->handover_status,
                ], [
                    "confirmed_at" => $marketing->confirmed_at,
                ]);
            }

            return "fee sharing generated";
        }

        return "ok";
    }
}
