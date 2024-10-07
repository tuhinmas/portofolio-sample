<?php

namespace Modules\Personel\Actions\Marketing;

use Modules\Personel\Entities\MarketingFee;

class UpsertMarketingFeeAction
{
    public function __invoke(array $data, MarketingFee $marketing_fee = null) : MarketingFee
    {
        extract($data);
        return MarketingFee::updateOrCreate(
            [
                "personel_id" => $marketing_fee ? $marketing_fee->personel_id : $personel_id,
                "year" => $marketing_fee ? $marketing_fee->year : $year,
                "quarter" => $marketing_fee ? $marketing_fee->quarter : $quarter,
            ],
            $data
        );
    }
}
