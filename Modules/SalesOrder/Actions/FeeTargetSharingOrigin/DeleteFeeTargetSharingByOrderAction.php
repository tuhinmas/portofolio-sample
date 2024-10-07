<?php

namespace Modules\SalesOrder\Actions\FeeTargetSharingOrigin;

use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;

class DeleteFeeTargetSharingByOrderAction
{
    public function __invoke($sales_order)
    {
        return FeeTargetSharing::query()
            ->where("sales_order_id", $sales_order->id)
            ->delete();
    }
}
