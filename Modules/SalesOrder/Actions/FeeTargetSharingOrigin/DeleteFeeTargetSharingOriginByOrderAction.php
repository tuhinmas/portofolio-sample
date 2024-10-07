<?php

namespace Modules\SalesOrder\Actions\FeeTargetSharingOrigin;

use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;

class DeleteFeeTargetSharingOriginByOrderAction
{
    public function __invoke($sales_order)
    {
        return FeeTargetSharingSoOrigin::query()
            ->where("sales_order_id", $sales_order->id)
            ->delete();
    }
}
