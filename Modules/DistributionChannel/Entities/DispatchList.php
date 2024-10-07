<?php

namespace Modules\DistributionChannel\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Entities\Warehouse;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\DealerDeliveryAddress;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Modules\PromotionGood\Entities\DispatchPromotionDeliveryAddress;
use Modules\PromotionGood\Entities\DispatchPromotionDetail;
use Modules\PromotionGood\Entities\PromotionGoodRequest;

class DispatchList extends Model
{
    protected $table = 'view_dispatch_list';

    public $incrementing = false; 

    public $primary = "id"; 

    // protected $keyType = 'string';
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse', 'id')->withTrashed()->orderBy("code", "asc");
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'id_armada')->withTrashed();
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, "id", "dispatch_order_id")->where("status", "send");
    }

    public function deliveryOrderPromotion()
    {
        return $this->belongsTo(DeliveryOrder::class, "id", "dispatch_promotion_id")->where("status", "send");
    }

    public function addressDelivery()
    {
        return $this->hasOne(DealerDeliveryAddress::class, "id", "delivery_address_id");
    }

    public function addressDeliveryPromotion()
    {
        return $this->hasOne(DispatchPromotionDeliveryAddress::class, "id", "delivery_address_id");
    }

    public function pickupDispatchOriginals()
    {
        return $this->hasMany(PickupOrderDispatch::class, 'dispatch_id','id');
    }

    public function pickupDispatchPickuped()
    {
        return $this->hasMany(PickupOrderDispatch::class, 'dispatch_id','id')->where(function($q){
            $q->wherehas("pickupOrder", function($q){
                $q->where("status", "!=", "canceled");
            });
        }); 
    }

    public function promotionGoodRequest()
    {
        return $this->hasOne(PromotionGoodRequest::class, "id", "promotion_good_request_id");
    }

    public function dispatchOrder()
    {
        return $this->belongsTo(DispatchOrder::class, "id", "id");
    }

    public function dispatchPromotion()
    {
        return $this->belongsTo(DispatchPromotion::class, "id", "id");
    }
    
}
