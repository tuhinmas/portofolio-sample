<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 *
 */
trait HandOverOrderTrait
{
    public function statusFeeUpdate($sales_order)
    {
        $confirmation_time = confirmation_time($sales_order);

        /* order before last order */
        $previous_order = $this->sales_order->query()
            ->with([
                "invoice",
            ])
            ->confirmedOrder()
            ->where("store_id", $sales_order->store_id)
            ->where("sales_orders.id", "!=", $sales_order->id)
            ->leftJoin("invoices", "invoices.sales_order_id", "sales_orders.id")
            ->orderByRaw("if(sales_orders.type = 1, invoices.created_at, sales_orders.date) desc")
            ->select("sales_orders.*", DB::raw("if(sales_orders.type = 1, invoices.created_at, sales_orders.date) as confirmation_time"))
            ->whereRaw("if(sales_orders.type = 1, invoices.created_at <= '$confirmation_time' , sales_orders.date <= '$confirmation_time')")
            ->first();

        /**
         * if current marketing is different with last marketing and in the same day
         */
        if ($previous_order) {

            $current_status_fee = $sales_order->status_fee;

            /* order doest have log status fee before */
            if (!$sales_order->logStatusFeeOrder) {

                if ($sales_order->personel_id !== $previous_order->personel_id) {
                    $statusL1 = $this->status_fee->where('name', 'L1')->first();

                    if ($sales_order->dealer) {
                        $sales_order->dealer->status_fee = $statusL1->id;
                        $sales_order->dealer->save();
                    } else if ($sales_order->subDealer) {
                        $sales_order->subDealer->status_fee = $statusL1->id;
                        $sales_order->subDealer->save();
                    }
                    $current_status_fee = $statusL1->id;
                } else {
                    if ($sales_order->is_office == false) {
                        $statusL1 = $this->status_fee->where('name', 'L1')->first();
                        $statusL2 = $this->status_fee->where('name', 'L2')->first();
                        $statusL3 = $this->status_fee->where('name', 'L3')->first();
                        $statusR = $this->status_fee->where('name', 'R')->first();
    
                        if ($sales_order->dealer) {
                            if ($sales_order->dealer->status_fee == $statusL1->id) {
                                $sales_order->dealer->status_fee = $statusL2->id;
                                $current_status_fee = $statusL2->id;
                            } else if ($sales_order->dealer->status_fee == $statusL2->id) {
                                $sales_order->dealer->status_fee = $statusL3->id;
                                $current_status_fee = $statusL3->id;
                            } else if ($sales_order->dealer->status_fee == $statusL3->id) {
                                $sales_order->dealer->status_fee = $statusR->id;
                                $current_status_fee = $statusR->id;
                            }
                            $sales_order->dealer->save();
                        }else if ($sales_order->subDealer) {
                            if ($sales_order->subDealer->status_fee == $statusL1->id) {
                                $sales_order->subDealer->status_fee = $statusL2->id;
                                $current_status_fee = $statusL2->id;
                            } else if ($sales_order->subDealer->status_fee == $statusL2->id) {
                                $sales_order->subDealer->status_fee = $statusL3->id;
                                $current_status_fee = $statusL3->id;
                            } else if ($sales_order->subDealer->status_fee == $statusL3->id) {
                                $sales_order->subDealer->status_fee = $statusR->id;
                                $current_status_fee = $statusR->id;
                            }
                            $sales_order->subDealer->save();
                        }
                    }

                }
            }

            return [
                "previous_order" => $previous_order->id,
                "type" => $previous_order->type,
                "current_status_fee" => $current_status_fee
            ];
        }
    }
}
