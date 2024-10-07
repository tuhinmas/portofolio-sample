<?php

namespace Modules\Personel\Actions;

use Modules\SalesOrder\Actions\GetFeeSharingSoOriginByPersonelYearQuarterAction;
use Modules\SalesOrder\ClassHelper\FeeMarketingRegulerTotalDataMapper;

class GetFeeRegulerTotalByPersonelYearQuarterAction
{
    public function __invoke(
        GetFeeSharingSoOriginByPersonelYearQuarterAction $fee_sharing_origins,
        FeeMarketingRegulerTotalDataMapper $fee_sharing_origin_mapper,
        array $payload,
        bool $save_log = false
    ) {
        return $fee_sharing_origin_mapper($fee_sharing_origins($payload), $save_log);
    }
}
