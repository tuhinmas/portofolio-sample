<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\Personel\Entities\Personel;

class ExportConfirmedSale extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        "date_settle"
    ];
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\ExportConfirmedSaleFactory::new();
    }

    public function salesOrderDetail()
    {
        return $this->hasMany(SalesOrderDetail::class, "sales_order_id", "sales_id");
    }

    public function statusFee()
    {
        return $this->belongsTo(StatusFee::class,"status_fee_id","id");
    }

    public function counter()
    {
        return $this->belongsTo(Personel::class,"counter_id","id");
    }

    public function distributor()
    {
        return $this->belongsTo(DealerV2::class,"distributor_id","id");
    }

    public function getDateSettleAttribute()
    {
        if($this->payment_status == "settle"){
            return $this->order_date;
        }else{
            return "-";
        }
    }
}
