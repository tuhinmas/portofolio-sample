<?php

namespace Modules\SalesOrder\Actions\Order;

use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealer\Actions\Order\GetLastOrderDealerAction;

class GetStatusFeeForOrderDependSuggestionAction
{
    /**
     * Undocumented function
     *
     * @param [type] $sales_order
     * @return void
     */
    public function __invoke($sales_order)
    {
        /*
        |================================================================================================================================
        | STATUS FEE RULES
        | 1. if order from office (is_office = true) status fee is reguler
        | 2. first order of dealer/ sub dealer and not from office also considered as reguler, or last order from office
        | 3. every new order will raise status fee, only in fiew condition.
        | 4. if last order if from different marketing, status fee will to L1.
        | 5. according point 4, if last order if from office then diffrent marketing (actually office) will not counted
        | 6. diffierent marketing if interspersed by office not counted.
        | 7. indirect sales with same nota date will have same status fee
        |
        | e.g.
        | * marketing A (L2) -> office (R) -> marketing A (L3)
        | * marketing A (L3) -> office (R) -> marketing B (L1)
        | * marketing A (L1) -> marketing A (L2)
        | * marketing A (L3) -> marketing B (L1)
        |------------------------------------------------------------------------------------------------------------------
        |
         */

        $status_fee_reguler = StatusFee::query()
            ->where("name", "R")
            ->first();

        $L1 = StatusFee::query()
            ->where("name", "L1")
            ->first();

        $status_fee_id = $status_fee_reguler?->id;
        if ($sales_order->is_office) {
            $status_fee_id = $status_fee_reguler?->id;
        } else {

            /* get last order of dealer or sub dealer */
            $last_order = (new GetLastOrderDealerAction)($sales_order, confirmation_time($sales_order)->format("Y-m-d H:i:s"));

            if ($last_order) {

                /* last order is from office, need to check order before this order */
                if ($last_order->is_office) {

                    $last_order_before = (new GetLastOrderDealerAction)($last_order, confirmation_time($last_order)->format("Y-m-d H:i:s"));

                    /**
                     * last order before not found, status fee will reguler
                     * rules point 2
                     */
                    if (!$last_order_before) {
                        $status_fee_id = $L1->id;
                    } else {

                        /**
                         * possible order office between two oprder from marketing
                         */
                        while ($last_order_before->is_office) {
                            $order_before = (new GetLastOrderDealerAction)($last_order_before, confirmation_time($last_order_before)->format("Y-m-d H:i:s"));
                            if (!$order_before) {
                                break;
                            } else {
                                $last_order_before = $order_before;
                            }
                        }

                        if ($last_order_before->is_office) {
                            $status_fee_id = $L1->id;
                        } else {

                            /* rules point 4 */
                            if ($last_order_before->personel_id != $sales_order->personel_id) {
                                $status_fee_id = $L1->id;
                            }

                            /* ruls point 3 */
                            else {
                                if (confirmation_time($last_order_before)->format("Y-m-d") == confirmation_time($sales_order)->format("Y-m-d")) {
                                    $status_fee_id = $last_order_before->status_fee_id;
                                } else {
                                    $status_fee_id = self::upgradeStatusFee($last_order_before->statusFeeShould ? $last_order_before->statusFeeShould->status_fee_id : $last_order_before->status_fee_id);
                                }
                            }
                        }
                    }
                }

                /* last order is from marketing */
                else {

                    /* rules point 4 */
                    if ($last_order->personel_id != $sales_order->personel_id) {
                        $status_fee_id = $L1->id;
                    }

                    /* ruls point 3 */
                    else {
                        if (confirmation_time($last_order)->format("Y-m-d") == confirmation_time($sales_order)->format("Y-m-d")) {
                            $status_fee_id = $last_order->status_fee_id;
                        } else {
                            $status_fee_id = self::upgradeStatusFee($last_order->statusFeeShould ? $last_order->statusFeeShould->status_fee_id : $last_order->status_fee_id);
                        }

                        // dd([
                        //     "status_fee_id" => $status_fee_id,
                        //     "last_order" => confirmation_time($last_order)->format("Y-m-d"),
                        //     "current_order" => confirmation_time($sales_order)->format("Y-m-d")
                        // ]);
                    }
                }
            }

            /* dealer or sub dealer has never made an order */
            else {
                $status_fee_id = $status_fee_reguler?->id;
            }
        }
        return $status_fee_id;
    }

    public static function upgradeStatusFee($status_fee_id)
    {
        $L1 = StatusFee::query()
            ->where("name", "L1")
            ->first();

        $L2 = StatusFee::query()
            ->where("name", "L2")
            ->first();

        $L3 = StatusFee::query()
            ->where("name", "L3")
            ->first();

        $status_fee_reguler = StatusFee::query()
            ->where("name", "R")
            ->first();

        return match ($status_fee_id) {
            $L1->id => $L2->id,
            $L2->id => $L3->id,
            $L3->id => $status_fee_reguler->id,
            $status_fee_reguler->id => $status_fee_reguler->id,
        };
    }
}
