<?php

namespace Modules\SalesOrder\Actions;

use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;

class UpsertFeeTargetSharingSoOrigin
{
    public function __invoke(array $data, FeeTargetSharingSoOrigin $fee_target_sharing_origin = null): FeeTargetSharingSoOrigin
    {
        return FeeTargetSharingSoOrigin::updateOrCreate(
            [
                "marketing_id" => $data["marketing_id"],
                "personel_id" => $data["personel_id"],
                "position_id" => $data["position_id"],
                "sales_order_origin_id" => $data["sales_order_origin_id"],
                "sales_order_id" => $data["sales_order_id"],
                "sales_order_detail_id" => $data["sales_order_detail_id"],
                "product_id" => $data["product_id"],
                "status_fee_id" => $data["status_fee_id"],
                "status_fee_percentage" => $data["status_fee_percentage"],
                "quantity_unit" => $data["quantity_unit"],
                "fee_percentage" => $data["fee_percentage"],
            ],
            $data
        );
    }
}
