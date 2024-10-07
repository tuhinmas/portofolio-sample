<?php

namespace Modules\Personel\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogMarketingFeeCounter extends Model
{
    use HasFactory;
    
    protected $table = "log_marketing_fee_counter";
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\LogMarketingFeeCounterFactory::new();
    }

    public function salesOrder(){
        return $this->hasOne(SalesOrder::class, "id", "sales_order_id");
    }
}
