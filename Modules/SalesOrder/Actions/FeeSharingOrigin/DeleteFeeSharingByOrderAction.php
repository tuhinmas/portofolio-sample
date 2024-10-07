<?php

namespace Modules\SalesOrder\Actions\FeeSharingOrigin;

use Modules\SalesOrder\Entities\FeeSharingSoOrigin;

class DeleteFeeSharingByOrderAction
{
    public function __invoke($sales_order)
    {
        return FeeSharingSoOrigin::query()
            ->where("sales_order_id", $sales_order->id)
            ->delete();
    }
}
