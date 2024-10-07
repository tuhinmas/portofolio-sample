<?php

namespace Modules\Personel\Actions;

use Modules\SalesOrder\ClassHelper\FeeMarketingRegulerActiveDataMapper;
use Modules\SalesOrder\Actions\GetFeeSharingSoOriginByPersonelYearQuarterAction;

class GetFeeRegulerActiveByPersonelYearQuarterAction
{
    /**
     * 
     *
     * @param GetFeeSharingSoOriginByPersonelYearQuarterAction $fee_sharing_origins
     * @param FeeMarketingRegulerActiveDataMapper $fee_sharing_origin_active_mapper
     * @param array $payload
     * payload include personel_id, year, quarter, sales_order
     * @param array $status
     * @param boolean $save_log
     * @return void
     */
    public function __invoke(
        GetFeeSharingSoOriginByPersonelYearQuarterAction $fee_sharing_origins,
        FeeMarketingRegulerActiveDataMapper $fee_sharing_origin_active_mapper,
        array $payload,
        array $status,
        bool $save_log = false
    ) {
        return $fee_sharing_origin_active_mapper($fee_sharing_origins($payload), $status, $save_log);
    }
}
