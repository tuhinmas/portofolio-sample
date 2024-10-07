<?php

namespace Modules\Invoice\Listeners;

use Carbon\Carbon;
use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Invoice\Events\FeeTargetMarketingEvent;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrderV2\Entities\FeeTargetSharingOrigin;

class FeeTargetSharingGeneratorListener
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
        FeeTargetSharingSoOrigin $fee_target_sharing_origin, 
        LogFeeTargetSharing $log_fee_target_sharing,
        FeePosition $fee_position,
        Personel $personel, 
        )
    {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_fee_target_sharing = $log_fee_target_sharing;
        $this->fee_position = $fee_position;
        $this->personel = $personel;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeTargetMarketingEvent $event)
    {

        /**
         * generate fee target sharing
         */
        return $this->feeTargetSharingOriginGenerator($event->invoice->salesOrder);

        /*
        |-----------------------
        | PENDING
        |----------------
         */
        $log = LogFeeTargetSharing::updateOrCreate([
            "sales_order_id" => $event->invoice->salesOrder->id,
            "type" => $event->invoice->salesOrder->type,
        ]);

        if (empty($event->invoice->salesOrder->counter_id)) {

            $active_contract = $this->distributorActiveContract($event->invoice->salesOrder->store_id);

            if (!$active_contract) {
                $marketing_get_fee_target = collect();

                /* get marketing with all his supervisor to top supervisor */
                $marketing_list = [];
                $marketing_supervisor = $this->parentPersonel($event->invoice->salesOrder->personel_id);
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
                    ->where("id", $event->invoice->salesOrder->personel_id)
                    ->first();

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
                if ($event->invoice->salesOrder->dealer !== null) {
                    $store = $event->invoice->salesOrder->dealer;
                } else {
                    $store = $event->invoice->salesOrder->subDealer;
                }

                /* handover store from another marketing */
                $is_handover = $event->invoice->salesOrder->statusFee;

                $event->invoice->salesOrder->sales_order_detail->each(function ($order_detail) use ($marketing_list, $personel_on_purchase, $rm_fee_percentage, $marketing_buyer_join_date_days, $store, &$marketing_get_fee_target, $fee_position, $event, $is_handover) {

                    /* marketing get fee target */
                    collect($fee_position)
                        ->each(function ($position) use ($marketing_list, $personel_on_purchase, $rm_fee_percentage, $marketing_buyer_join_date_days, $store, &$marketing_get_fee_target, $event, $order_detail, $is_handover) {
                            $marketing = $marketing_list->where("position_id", $position->position_id)->first();
                            $detail = null;

                            if ($position->fee_as_marketing == 1) {
                                $personel_id = null;

                                /* fee purchaser */
                                if ($personel_on_purchase) {
                                    if ($marketing_buyer_join_date_days >= 90 || $event->invoice->salesOrder->statusFee->name == "R") {
                                        $personel_id = $personel_on_purchase->id;
                                    }
                                }

                                $detail = [
                                    "personel_id" => $personel_id,
                                    "position_id" => $rm_fee_percentage->position_id,
                                    "position_name" => $rm_fee_percentage->position->name,
                                    "sales_order_id" => $event->invoice->salesOrder->id,
                                    "type" => $event->invoice->salesOrder->type,
                                    "sales_order_detail_id" => $order_detail->id,
                                    "product_id" => $order_detail->product_id,
                                    "quantity_unit" => $order_detail->quantity,
                                    "fee_percentage" => $rm_fee_percentage->position->fee ? $rm_fee_percentage->position->fee->fee : $rm_fee_percentage->fee,
                                    "status_fee_id" => $event->invoice->salesOrder->status_fee_id,
                                    "status_fee_percentage" => $is_handover->percentage,
                                    "fee_nominal" => 0,
                                    "join_days" => $marketing_buyer_join_date_days,
                                    "confirmed_at" => $event->invoice->created_at,
                                ];

                                $marketing_get_fee_target->push(collect($detail));

                            } else {

                                $marketing_join_days = $marketing ? ($marketing->join_date ? Carbon::parse($marketing->join_date)->diffInDays(($event->invoice->salesOrder->type == "2" ? $event->invoice->salesOrder->created_at : $event->invoice->created_at), false) : 0) : 0;

                                if ($marketing_join_days < 90 && $event->invoice->salesOrder->statusFee->name !== "R") {
                                    $marketing = null;
                                }

                                $detail = [
                                    "personel_id" => $marketing ? $marketing->id : null,
                                    "position_id" => $position->position_id,
                                    "position_name" => $position->position->name,
                                    "sales_order_id" => $event->invoice->salesOrder->id,
                                    "type" => $event->invoice->salesOrder->type,
                                    "sales_order_detail_id" => $order_detail->id,
                                    "product_id" => $order_detail->product_id,
                                    "quantity_unit" => $order_detail->quantity,
                                    "fee_percentage" => $position ? $position->fee : null,
                                    "status_fee_id" => $event->invoice->salesOrder->status_fee_id,
                                    "status_fee_percentage" => $is_handover->percentage,
                                    "fee_nominal" => 0,
                                    "join_days" => $marketing_join_days,
                                    "confirmed_at" => $event->invoice->created_at,
                                ];

                                $marketing_get_fee_target->push(collect($detail));
                            }

                            return $detail;
                        });
                });
                /**
                 * generate fee target sharing
                 */
                $marketing_get_fee_target->each(function ($marketing) use ($event) {
                    $fee_target = $this->fee_target_sharing_origin->updateOrCreate([
                        "personel_id" => $marketing["personel_id"],
                        "position_id" => $marketing["position_id"],
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
                        "is_active" => $event->invoice->payment_status == "settle" ? 1 : 0,
                    ]);

                    $log = LogFeeTargetSharing::updateOrCreate([
                        "sales_order_id" => $marketing["sales_order_id"],
                        "type" => $marketing["type"],
                    ]);
                });

                // return $marketing_get_fee_target->sortBy("fee_percentage");
                return "fee target sharing generated";
            }

            return "distributor contract found";
        }

        return "follow up order get no fee target";
    }
}
