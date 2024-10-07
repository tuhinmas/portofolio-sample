<?php

namespace Modules\Invoice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        if (request()->method() != "GET") {
            return parent::toArray($request);
        }

        $transfered_credit_memo_nominal = DB::table('credit_memo_details as cmd')
            ->join("credit_memos as cm", "cm.id", "cmd.credit_memo_id")
            ->whereNull("cm.deleted_at")
            ->whereNull("cmd.deleted_at")
            ->where("cm.origin_id", $this->id)
            ->where("cm.destination_id", "<>", $this->id)
            ->whereIn("cm.status", valid_credit_memo_statuses())
            ->sum("cmd.total");
        $non_return_payment = match (true) {
            $this->relationLoaded("payment") => $this->payment->filter(fn($payment) => !$payment->is_credit_memo)->sum("nominal"),
            default => 0,
        };

        if ($this->relationLoaded("salesOrder")) {
            if ($this->resource->salesOrder->sales_order_detail) {
                if ($this->relationLoaded("creditMemos")) {
                    if ($this->resource->creditMemos->count() > 0) {
                        $this->resource
                            ->creditMemos
                            ->loadMissing([
                                "creditMemoDetail",
                            ]);

                        $credit_memo_details = $this->resource
                            ->creditMemos
                            ->pluck("creditMemoDetail")
                            ->flatten()
                            ->groupBy("product_id")
                            ->map(function ($credit_memo_detail) {
                                return $credit_memo_detail->sum("total");
                            });
                        $this->resource
                            ->salesOrder
                            ->sales_order_detail
                            ->transform(function ($order_detail) use ($credit_memo_details) {
                                $order_detail["returned_nominal"] = $credit_memo_details->filter(fn($memo, $product_id) => $product_id == $order_detail->product_id)->first() ?? 0;
                                return $order_detail;
                            });

                    }
                    $this->resource["transfered_credit_memo_nominal"] = $transfered_credit_memo_nominal;
                    $this->resource["non_return_payment"] = $non_return_payment;
                    return parent::toArray($request);
                } else {
                    $this->resource
                        ->salesOrder
                        ->sales_order_detail
                        ->transform(function ($order_detail) {
                            $order_detail["returned_nominal"] = ($order_detail->returned_quantity * $order_detail->unit_price) - ($order_detail->discount > 0 ? $order_detail->discount / $order_detail->quantity * $order_detail->returned_quantity : 0);
                            return $order_detail;
                        });

                    $this->resource["transfered_credit_memo_nominal"] = $transfered_credit_memo_nominal;
                    $this->resource["non_return_payment"] = $non_return_payment;
                    return parent::toArray($request);
                }
            }
        }
        $this->resource["transfered_credit_memo_nominal"] = $transfered_credit_memo_nominal;
        $this->resource["non_return_payment"] = $non_return_payment;
        return parent::toArray($request);
    }

    public function with($request)
    {
        return [
            "response_code" => "00",
            "response_message" => "success",
        ];
    }
}
