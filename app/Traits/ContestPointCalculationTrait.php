<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Traits\DistributorTrait;
use Spatie\Activitylog\Contracts\Activity;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Contest\Entities\ContestParticipant;
use Modules\Contest\Entities\ContestPointOrigin;
use Modules\Contest\Entities\ContestDealerGrading;
use Modules\Contest\Entities\LogContestPointOrigin;
use Modules\Contest\Entities\ContestParticipantAdjustment;
use Modules\Contest\ClassHelper\ContestConsideredPointRules;

/**
 * recalculate point
 */
trait ContestPointCalculationTrait
{
    use DistributorTrait;

    public function recalculateContestParticipantPoint($request, $contest_participant)
    {
        if ($contest_participant) {

            /**
             * delete all point origin according contract
             */
            if ($request->update_contest_point_origin) {
                $delete_contest_point_origin = ContestPointOrigin::query()
                    ->where("contest_participants_id", $contest_participant->id)
                    ->delete();

                $delete_contest_point_origin = ContestPointOrigin::query()
                    ->withTrashed()
                    ->whereNotNull("deleted_at")
                    ->whereDate("deleted_at", "<=", now()->subDays(30))
                    ->where("contest_participants_id", $contest_participant->id)
                    ->forceDelete();
            }

            if ($contest_participant->participant_status == "4") {

                $contes_dealer_grading = ContestDealerGrading::query()
                    ->select("grading_id")
                    ->where("contest_id", $contest_participant->contest_id)
                    ->get()
                    ->map(function ($data) {
                        return $data->grading_id;
                    })
                    ->toArray();

                $old_contract = [
                    "point" => $contest_participant->point,
                    "adjustment_point" => $contest_participant->adjustment_point,
                    "active_point" => $contest_participant->active_point,
                    "redeemable_point" => $contest_participant->redeemable_point,
                ];

                $admitted_date = $contest_participant->admitted_date ?: $contest_participant->registration_date;
                if ($admitted_date <= $contest_participant->contest->early_bird) {
                    $admitted_date = $contest_participant->contest->period_date_start;
                }

                $order_date_until = $contest_participant->redeem_date ?: $contest_participant->contest->period_date_end;

                /**
                 * contract is active but not redeem, then need to
                 * check next contract to get end of this
                 * contract, becouse redeem_date no
                 * filled yet
                 */
                if (
                    $contest_participant->redeem_status == "1"
                    && !$contest_participant->redeem_date
                    && $contest_participant->is_dealer_support
                ) {
                    $next_contract = ContestParticipant::query()
                        ->where("contest_id", $contest_participant->contest_id)
                        ->where("dealer_id", $contest_participant->dealer_id)
                        ->where("participant_status", "!=", 3)
                        ->where("admitted_date", ">", $contest_participant->admitted_date)
                        ->orderBy("admitted_date")
                        ->first();

                    if ($next_contract) {
                        if (in_array($next_contract->participant_status, [1, 2])) {
                            return (object) [
                                "point" => "skipped contract",
                                "adjustment_point" => "skipped contract",
                                "active_point" => "skipped contract",
                                "redeemable_point" => "skipped contract",
                            ];
                        } else if ($next_contract->participant_status == "4") {
                            $order_date_until = Carbon::parse($next_contract->admitted_date)->subDay();
                        }
                    }
                }

                /**
                 * get order of store
                 */
                $sales_orders = SalesOrder::query()
                    ->with([
                        "distributor",
                        "contestPointOrigin",
                        "invoice" => function ($QQQ) {
                            return $QQQ->with([
                                "lastPayment",
                            ]);
                        },
                        "sales_order_detail",
                    ])
                    ->where("store_id", ($contest_participant->dealer_id ? $contest_participant->dealer_id : $contest_participant->sub_dealer_id))
                    ->consideredOrderForContest($contest_participant, $admitted_date, $order_date_until)
                    ->whereHas("sales_order_detail")
                    ->orderBy("order_number")
                    ->get()
                    ->filter(function ($order) use ($contest_participant) {
                        return (new ContestConsideredPointRules)->isConsideredPoint($contest_participant, $order);
                    })
                    ->values();

                /**
                 * generate contest point origin
                 */
                if ($sales_orders->count() > 0) {
                    $point_detail = collect();
                    $sales_orders->each(function ($order) use ($contest_participant, &$point_detail) {

                        $is_active = false;
                        if ($order->type == "2") {
                            $is_active = true;
                        } else {
                            if ($order->invoice) {
                                if ($order->invoice->payment_time <= $contest_participant->contest->maximum_settle_days && $order->invoice->payment_status == "settle") {
                                    $is_active = true;
                                }
                            }
                        }

                        /* check contest point according point reference */
                        if ($order->sales_order_detail->count() > 0) {
                            $point_references = $contest_participant->contest->contestPointRefrence;

                            $contest_participant = $contest_participant;
                            collect($order->sales_order_detail)->each(function ($order_detail) use ($point_references, $contest_participant, &$point_detail, $order, $is_active) {
                                $point_product = $point_references
                                    ->where("product_id", $order_detail->product_id)
                                    ->where("periodic_status", "0");

                                $point_product_periodic = $point_references
                                    ->where("product_id", $order_detail->product_id)
                                    ->where("periodic_status", "1")
                                    ->where("periodic_start_date", "<=", confirmation_time($order)->format("Y-m-d"))
                                    ->where("periodic_end_date", ">=", confirmation_time($order)->format("Y-m-d"));

                                if ($point_product_periodic) {
                                    if ($point_product_periodic->count() > 0) {
                                        $point_product = $point_product_periodic;
                                    }
                                }

                                if ($point_product->count() > 0) {

                                    $point = 0;
                                    $quantity = $order_detail->quantity - $order_detail->returned_quantity;

                                    /**
                                     * calculate product point
                                     * using euclidean
                                     */
                                    collect($point_product)->sortByDesc("minimum_quantity")->each(function ($point_per_quantity) use (&$point, $order_detail, &$quantity, &$point_detail, $contest_participant, $order) {
                                        $corresponding_point = floor($quantity / $point_per_quantity->minimum_quantity);
                                        $modulo = $quantity % $point_per_quantity->minimum_quantity;
                                        $point += $corresponding_point * $point_per_quantity->product_point;
                                        $point_detail->push(collect([
                                            "point" => $point,
                                            "sales_order_details_id" => $order_detail->id,
                                            "product_id" => $order_detail->product_id,
                                            "quantity" => $quantity,
                                            "contest_participants_id" => $contest_participant->id,
                                            "status_point" => 1,
                                            "periodic_status" => $point_per_quantity->periodic_status,
                                            "confirmed_at" => confirmation_time($order),
                                        ]));

                                        $quantity = $modulo;
                                    });

                                    $point_origin = ContestPointOrigin::updateOrCreate([
                                        "sales_order_details_id" => $order_detail->id,
                                    ], [
                                        "point" => $point,
                                        "active_point" => $is_active,
                                        "contest_participants_id" => $contest_participant->id,
                                        "status_point" => true,
                                        "confirmed_at" => confirmation_time($order),
                                        "note" => $point <= 0 ? "quantity tidak mencukupi" : null,
                                    ]);

                                    LogContestPointOrigin::updateOrCreate([
                                        "sales_order_detail_id" => $order_detail->id,
                                    ], [
                                        "status_point" => true,
                                        "status_active_point" => $is_active,
                                    ]);
                                } else {
                                    ContestPointOrigin::updateOrCreate([
                                        "sales_order_details_id" => $order_detail->id,
                                    ], [
                                        "point" => 0,
                                        "active_point" => false,
                                        "contest_participants_id" => $contest_participant->id,
                                        "status_point" => 0,
                                        "confirmed_at" => confirmation_time($order),
                                        "note" => "produk tidak termasuk dalam acuan kontes",
                                    ]);

                                    LogContestPointOrigin::updateOrCreate([
                                        "sales_order_detail_id" => $order_detail->id,
                                    ], [
                                        "status_point" => false,
                                        "status_active_point" => false,
                                    ]);
                                }
                            });
                        }
                    });
                }

                /**
                 * calculate contest point
                 */
                $contest_point_origin = ContestPointOrigin::query()
                    ->with([
                        "salesOrder",
                    ])
                    ->where("contest_participants_id", $contest_participant->id)
                    ->whereHas("salesOrderDetail")
                    ->whereHas("salesOrder", function ($QQQ) use ($contest_participant, $admitted_date, $order_date_until) {
                        return $QQQ->consideredOrderForContest($contest_participant, $admitted_date, $order_date_until);
                    })
                    ->get();

                /**
                 * contest participant adjsutment
                 */
                $contest_participant_adjustment = ContestParticipantAdjustment::query()
                    ->where("contest_participants_id", $contest_participant->id)
                    ->sum("adjustment_poin");

                /**
                 * update contest participant total point
                 */
                $contest_participant->point = $contest_point_origin->sum("point");
                $contest_participant->active_point = $contest_point_origin->where("active_point", true)->sum("point");
                $contest_participant->adjustment_point = (int) $contest_participant_adjustment;
                $contest_participant->redeemable_point = $contest_point_origin->where("active_point", true)->sum("point") + ($contest_participant_adjustment);
                $contest_participant->save();

                /**
                 * update participantion status
                 */
                $contest_minimum_target = $contest_participant->contest->contestMinimumPrize->point_target;

                if (!$contest_participant->is_dealer_support) {
                    $contest_minimum_target = $contest_participant->contestPrize->point_target;
                }

                if ($contest_participant->participation_status == "4") {
                    return true;
                }

                if ($contest_participant->redeemable_point >= $contest_minimum_target && $contest_participant->contest->redeem_date_limit >= now()->endOfDay()) {
                    $contest_participant->participation_status = "2";
                } else if ($contest_participant->redeemable_point < $contest_minimum_target && $contest_participant->contest->redeem_date_limit < now()->endOfDay() && $contest_participant->participation_status != "4") {
                    $contest_participant->participation_status = "3";
                } else {
                    $contest_participant->participation_status = "1";
                }

                /**
                 * featured test
                 */
                if ($contest_participant->redeem_date) {
                    $target_point = $contest_participant?->contestPrize->point_target;
                    $contest_participant->point -= $target_point;
                    $contest_participant->active_point -= $target_point;
                    $contest_participant->redeemable_point -= $target_point;
                }

                $contest_participant->save();

                $test = activity()
                    ->causedBy(auth()->id())
                    ->performedOn($contest_participant)
                    ->withProperties([
                        "old" => $old_contract,
                        "attributes" => $contest_participant,
                    ])
                    ->tap(function (Activity $activity) {
                        $activity->log_name = 'sync';
                    })
                    ->log('contest point syncronize');

                return $contest_participant;
                dump($contest_participant);
            }
            return "non confirmed contract";
        }

        return "no contract contest found";
    }
}
