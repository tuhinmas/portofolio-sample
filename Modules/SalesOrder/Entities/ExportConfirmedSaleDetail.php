<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealerV2\Entities\SubDealerV2;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class ExportConfirmedSaleDetail extends Model
{
    use HasFactory;

    protected $appends = [
        "date_settle",
        "store"
    ];

    protected $guarded = [];
    protected $table = "export_confirmed_sales_detail";
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\ExportConfirmedSaleDetailFactory::new();
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrderV2::class,"sales_order_id","id");
    }

    public function salesOrderConfirmed()
    {
        return $this->belongsTo(ExportConfirmedSale::class,"sales_order_id","sales_id");
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

    public function dealer()
    {
        return $this->belongsTo(DealerV2::class,"store_id","id");
    }

    public function agencyLevel()
    {
        return $this->belongsTo(AgencyLevel::class,"agency_level_id","id");
    }

    public function subDealer()
    {
        return $this->belongsTo(SubDealerV2::class,"store_id","id");
    }

    public function marketing()
    {
        return $this->belongsTo(Personel::class,"personel_id","id");
    }

    public function grading()
    {
        return $this->belongsTo(Grading::class,"grading_id","id");
    }

    public function getStoreAttribute()
    {
        if($this->dealer()->first()){
            return 'CUST-'.$this->dealer()->first()->dealer_id;
        }elseif($this->subDealer()->first()){
            return 'CUST-SUB-'.$this->subDealer()->first()->sub_dealer_id;
        }else{
            return "-";
        }
    }
}
