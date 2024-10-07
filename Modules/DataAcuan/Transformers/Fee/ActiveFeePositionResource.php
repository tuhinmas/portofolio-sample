<?php

namespace Modules\DataAcuan\Transformers\Fee;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePositionHistory;

class ActiveFeePositionResource extends JsonResource
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
        $fee_position = [];
        $date_start = now()->startOfYear()->format("Y-m-d H:i:s");
        $date_end = null;
        if ($this->resource instanceof FeePositionHistory) {
            $fee_position = collect($this->fee_position)
                ->map(function ($fee) {
                    $fee["position"] = DB::table('positions')->where("id", $fee["position_id"])->select("name")->first();
                    $fee["fee_cash_minimum_order"] = DB::table('agency_levels')->where("id", $fee["fee_cash_minimum_order"])->select("id", "name")->first();
                    return $fee;
                })
                ->toArray();

            $date_start = $this->date_start;
            $date_end = $this->date_end;
        } else {
            $fee_position = collect($this->resource)
                ->map(function ($fee) {
                    $fee_cash_minimum_order = $fee->feeCashMinimumOrder;
                    $fee->unsetRelation("feeCashMinimumOrder");
                    $fee["position"] = $fee["position"]->only("name");
                    $fee["fee_cash_minimum_order"] = $fee_cash_minimum_order->only("id", "name");
                    return $fee->only([
                        "fee",
                        "fee_cash",
                        "follow_up",
                        "position_id",
                        "fee_sc_on_order",
                        "maximum_settle_days",
                        "fee_cash_minimum_order",
                        "position",
                    ]);
                })
                ->toArray();
        }

        return [
            "date_start" => $date_start,
            "date_end" => $date_end,
            "active_fee_position" => $fee_position,
        ];
    }
}
