<?php

namespace Modules\Personel\Actions;

use Modules\SalesOrderV2\Entities\FeeTargetSharing;

class UpsertFeeTargetSharingAction
{
    public function __invoke(array $data, FeeTargetSharing $fee_target_sharing = null): FeeTargetSharing
    {
        return FeeTargetSharing::updateOrCreate(
            [
                'marketing_id' => $fee_target_sharing ? $fee_target_sharing->marketing_id : $data["marketing_id"],
                'personel_id' => $fee_target_sharing ? $fee_target_sharing->personel_id : $data["personel_id"],
                'year' => $fee_target_sharing ? $fee_target_sharing->personel_id : $data["year"],
                'quarter' => $fee_target_sharing ? $fee_target_sharing->personel_id : $data["quarter"],
                'position_id' => $fee_target_sharing ? $fee_target_sharing->position_id : $data["position_id"],
                'product_id' => $fee_target_sharing ? $fee_target_sharing->product_id : $data["product_id"],
                'status_fee_id' => $fee_target_sharing ? $fee_target_sharing->status_fee_id : $data["status_fee_id"],
            ],
            $data
        );
    }
}
