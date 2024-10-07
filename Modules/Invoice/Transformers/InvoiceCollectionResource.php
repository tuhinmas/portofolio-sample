<?php

namespace Modules\Invoice\Transformers;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class InvoiceCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $transfered_credit_memo_nominal_data = collect();
        if ($request->include_transfered_credit_memo_nominal) {
            $transfered_credit_memo_nominal_data = DB::table('credit_memos')
                ->whereNull("deleted_at")
                ->where("status", valid_credit_memo_statuses())
                ->whereIn("origin_id", $this->collection->pluck("id")->toArray())
                ->get();
        }

        return [
            "response_code" => "00",
            "response_message" => "success",
            "data" => $this->collection
                ->transform(function ($invoice) use (&$transfered_credit_memo_nominal_data) {

                    $returned_nominal = match (true) {
                        $invoice->creditMemos->count() > 0 => $invoice->creditMemos->sum("total"),
                        default => $invoice
                            ->salesOrder
                            ->sales_order_detail
                            ->reduce(function ($nominal, $order_detail) {
                                return $nominal + ($order_detail->returned_quantity * $order_detail->unit_price) - ($order_detail->discount > 0 ? $order_detail->discount / $order_detail->quantity * $order_detail->returned_quantity : 0);
                            })
                    };

                    $transfered_credit_memo_nominal = match (true) {
                        $transfered_credit_memo_nominal_data->count() > 0 => $transfered_credit_memo_nominal_data->filter(fn($memo) => $memo->origin_id == $invoice->id && $memo->destination_id != $invoice->id)->sum("total"),
                        default => 0,
                    };
                    
                    $non_return_payment = match (true) {
                        $invoice->relationLoaded("payment") => $invoice->payment->filter(fn($payment) => !$payment->is_credit_memo)->sum("nominal"),
                        default => 0,
                    };
                    
                    $invoice["returned_nominal"] = $returned_nominal;
                    $invoice["transfered_credit_memo_nominal"] = $transfered_credit_memo_nominal;
                    $invoice["non_return_payment"] = $non_return_payment;

                    return $invoice;
                }),
        ];
    }
}
