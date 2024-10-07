<?php

namespace Modules\SalesOrder\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogStatusFeeOrder extends Model
{
    use HasFactory;
    use TimeSerilization;

    protected $fillable = [
        "sales_order_id"
    ];
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\LogStatusFeeOrderFactory::new();
    }

    public function salesOrder(){
        return $this->hasOne(SalesOrder::class, "id", "sales_order_id");
    }
}
