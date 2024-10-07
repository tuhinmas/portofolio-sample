<?php

namespace Modules\SalesOrder\Actions;

use Modules\SalesOrder\Entities\SalesOrderOrigin;

class UpsertSalesOrderOriginAction
{
    public function __invoke(array $data, SalesOrderOrigin $sales_order_origin = null): SalesOrderOrigin
    {
        return SalesOrderOrigin::updateOrCreate(
            ["id" => $sales_order_origin?->id],
            $data
        );
    }
}
