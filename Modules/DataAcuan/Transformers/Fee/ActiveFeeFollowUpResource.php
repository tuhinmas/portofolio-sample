<?php

namespace Modules\DataAcuan\Transformers\Fee;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DataAcuan\Entities\FeeFollowUpHistory;

class ActiveFeeFollowUpResource extends JsonResource
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
        $fee_follow_up = [];
        $date_start = now()->startOfYear()->format("Y-m-d H:i:s");
        $date_end = null;
        if ($this->resource instanceof FeeFollowUpHistory) {
            $fee_follow_up = collect($this->fee_follow_up)->toArray();
            $date_start = $this->date_start;
            $date_end = $this->date_end;
        } else {
            $fee_follow_up = collect($this->resource)
                ->sortBy("follow_up_days")
                ->map(function ($fee) {
                    return $fee
                        ->only([
                            "fee",
                            "settle_days",
                            "follow_up_days",
                        ]);
                })
                ->values();
        }

        return [
            "date_start" => $date_start,
            "date_end" => $date_end,
            "fee_follow_up" => $fee_follow_up,
        ];
    }

}
