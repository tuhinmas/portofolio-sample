<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogSalesOrderOrigin extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\LogSalesOrderOriginFactory::new ();
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, "sales_order_id", "id");
    }
}
