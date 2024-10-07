<?php

namespace Modules\DataAcuan\Transformers\Fee;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DataAcuan\Entities\StatusFeeHistory;

class ActiveStatusFeeResource extends JsonResource
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
        $status_fee = [];
        $date_start = now()->startOfYear()->format("Y-m-d H:i:s");
        $date_end = null;
        if ($this->resource instanceof StatusFeeHistory) {
            $status_fee = $this->status_fee;
            $date_start = $this->date_start;
            $date_end = $this->date_end;
        }
        else {
            $status_fee =  collect($this->resource)->sortBy("name")->values();
        }

        return [
            "date_start" => $date_start,
            "date_end" => $date_end,
            "status_fee" => $status_fee,
        ];
    }
}
