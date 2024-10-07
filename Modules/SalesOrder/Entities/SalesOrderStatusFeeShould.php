<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrderStatusFeeShould extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\SalesOrderStatusFeeShouldFactory::new();
    }

    public function salesOrder(){
        return $this->belongsTo(SalesOrder::class, "sales_order_id", "id");
    }
   
    public function statusFee(){
        return $this->belongsTo(StatusFee::class, "status_fee_id", "id");
    }
}
