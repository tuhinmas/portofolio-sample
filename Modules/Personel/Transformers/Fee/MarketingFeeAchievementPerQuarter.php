<?php

namespace Modules\Personel\Transformers\Fee;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MarketingFeeAchievementPerQuarter extends ResourceCollection
{
    use CollectionResourceWith;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($personel) {
            $fee_achievement = $personel->marketingFee[0]->fee_reguler_settle + $personel->marketingFee[0]->fee_target_settle;
            $fee_payment = $personel->marketingFee[0]->payment->sum("amount");
            return [
                "marketing" => [
                    "id" => $personel->id,
                    "name" => $personel->name,
                    "position" => $personel->position?->name,
                    "payment_status" => match (true) {
                        $fee_payment >= $fee_achievement && $fee_achievement > 0 => "dibayar",
                        $personel->marketingFee[0]->payment->count() > 0 => "dibayar sebagian",
                        default => "belum dibayar"
                    },
                ],
                "fee_reguler" => [
                    "total" => $personel->marketingFee[0]->fee_reguler_total,
                    "pending" => $personel->marketingFee[0]->fee_reguler_settle_pending,
                    "active" => $personel->marketingFee[0]->fee_reguler_settle,
                ],
                "fee_target" => [
                    "total" => $personel->marketingFee[0]->fee_target_total,
                    "pending" => $personel->marketingFee[0]->fee_target_settle_pending,
                    "active" => $personel->marketingFee[0]->fee_target_settle,
                ],
                "total_active" => $personel->marketingFee[0]->fee_reguler_settle + $personel->marketingFee[0]->fee_target_settle,
                "fee_paid" => $fee_payment,
                "fee_paid_remaining" => $fee_achievement - $fee_payment,
            ];
        });
    }
}
