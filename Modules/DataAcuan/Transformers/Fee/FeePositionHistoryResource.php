<?php

namespace Modules\DataAcuan\Transformers\Fee;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class FeePositionHistoryResource extends JsonResource
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
        $fee_position = collect($this->fee_position)
            ->map(function ($fee) {
                $fee["position"] = DB::table('positions')->where("id", $fee["position_id"])->select("name")->first();
                $fee["fee_cash_minimum_order"] = DB::table('agency_levels')->where("id", $fee["fee_cash_minimum_order"])->select("id", "name")->first();
                return $fee;
            })
            ->toArray();

        return [
            "id" => $this->id, 
            "date_start" => $this->date_start, 
            "date_end" => $this->date_end, 
            "fee_position" => $fee_position, 
            "created_at" => $this->created_at, 
            "updated_at" => $this->updated_at, 
            "deleted_at" => $this->deleted_at, 
        ];
    }
}
