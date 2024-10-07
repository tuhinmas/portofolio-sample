<?php

namespace Modules\SalesOrder\Transformers;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\DataAcuan\Entities\Position;

class FeePerProductOrderResource extends ResourceCollection
{
    use CollectionResourceWith;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $position_fee_reduction = Position::query()
            ->whereHas("fee", function ($QQQ) {
                return $QQQ->where("follow_up", true);
            })
            ->get()
            ->pluck("name")
            ->toArray();

        return $this->collection
            ->map(function ($order_detail) use ($request, $position_fee_reduction) {

                $is_handover = collect($order_detail->feeSharingOrigin)
                    ->where("handover_status", true)
                    ->first();

                $fee_reguler_netto = collect($order_detail->feeSharingOrigin)
                    ->where("personel_id", $request->personel_id)
                    ->reject(function ($origin) use ($is_handover) {
                        if ($is_handover) {
                            return $origin->fee_status == "purchaser";
                        }
                    })
                    ->sum("fee_shared");

                /* fee position exclude status fee or any fee deductions */
                $fee_reguler_bruto = collect($order_detail->feeSharingOrigin)
                    ->where("personel_id", $request->personel_id)
                    ->reject(function ($origin) use ($is_handover) {
                        if ($is_handover) {
                            return $origin->handover_status == true;
                        }
                    })
                    ->map(function ($origin) {
                        $origin->fee_shared = $origin->total_fee * $origin->fee_percentage / 100;
                        return $origin;
                    })
                    ->sum("fee_shared");

                /* fee reguler bruto minus sales counter deduction */
                $sales_counter_deduction = collect($order_detail->feeSharingOrigin)
                    ->where("personel_id", $request->personel_id)
                    ->reject(function ($origin) use ($is_handover) {
                        if ($is_handover) {
                            return $origin->handover_status == true;
                        }
                    })
                    ->map(function ($origin) {
                        $origin->fee_shared = $origin->total_fee * $origin->fee_percentage / 100;
                        return $origin;
                    })
                    ->map(function ($origin) {
                        if ($origin->sc_reduction_percentage > 0) {
                            $origin->fee_shared = $origin->fee_shared * $origin->sc_reduction_percentage / 100;
                        }
                        return $origin;
                    })
                    ->sum("fee_shared");

                $sales_counter_deduction_percentage = collect($order_detail->feeSharingOrigin)
                    ->where("personel_id", $request->personel_id)
                    ->filter(function ($origin) {
                        return $origin->sc_reduction_percentage > 0;
                    })
                    ->first()
                ?->sc_reduction_percentage;

                $order_detail->fee_reguler_total_product = $order_detail->marketing_fee_reguler;
                $order_detail->fee_reguler_bruto = $fee_reguler_bruto;
                $order_detail->sales_counter_deduction = $sales_counter_deduction;
                $order_detail->sales_counter_deduction_percentage = $sales_counter_deduction_percentage;
                $order_detail->position_fee_reduction = $position_fee_reduction;
                $order_detail->status_fee_handover_percentage = $order_detail->salesOrder->statusFee?->percentage;
                $order_detail->fee_reguler_netto = $fee_reguler_netto;

                return $order_detail->only([
                    "product",
                    "quantity",
                    "fee_reguler_total_product",
                    "fee_reguler_bruto",
                    "sales_counter_deduction",
                    "sales_counter_deduction_percentage",
                    "position_fee_reduction",
                    "fee_reguler_netto",
                    "status_fee_handover_percentage",
                ]);
            });
    }
}
