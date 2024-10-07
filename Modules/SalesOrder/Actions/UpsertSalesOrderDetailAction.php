<?php

namespace Modules\SalesOrder\Actions;

use Modules\SalesOrder\Entities\SalesOrderDetail;

class UpsertSalesOrderDetailAction
{
    public function __invoke(array $data, SalesOrderDetail $sales_order_detail = null) : SalesOrderDetail
    {
        return SalesOrderDetail::updateOrCreate(
            ["id" => $sales_order_detail?->id],
            $data
        );
    }
}
