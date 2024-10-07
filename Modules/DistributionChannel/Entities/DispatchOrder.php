<?php

namespace Modules\DistributionChannel\Entities;

use App\Traits\Uuids;
use App\Traits\MarketingArea;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Invoice\Entities\Invoice;
use Modules\DataAcuan\Entities\Driver;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Warehouse;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\PickupOrder\Entities\PickupOrderFile;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\KiosDealer\Entities\DealerDeliveryAddress;
use Modules\PromotionGood\Entities\PromotionGoodRequest;

class DispatchOrder extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use CascadeSoftDeletes;
    use SuperVisorCheckV2;
    use MarketingArea;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $table = 'discpatch_order';

    protected $cascadeDeletes = [
        "deliveryOrder",
        "receivingGood",
    ];
    protected $dates = ['deleted_at'];
    protected $guarded = [];
    protected $appends = [
        // "dispatch_order_weight",
        "is_received",
        "is_has_delivery_order",
    ];

    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\DispatchOrderFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    public function getIsReceivedAttribute()
    {
        return $this->wasReceived()->first() ? true : false;
    }

    public function getIsHasDeliveryOrderAttribute()
    {
        return $this->deliveryOrder()->where("status", "send")->first() ? true : false;
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, "id_armada", "id")->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "id_warehouse", "id")->withTrashed();
    }

    public function dispatchOrderDetail()
    {
        return $this->hasMany(DispatchOrderDetail::class, "id_dispatch_order", "id")->with("product","salesOrderDetail");
    }
    
    public function dispatchDetail()
    {
        return $this->hasMany(DispatchOrderDetail::class, "id_dispatch_order", "id")->with("product","salesOrderDetail");
    }

    public function dispatchOrderDetailExcludeProduct()
    {
        return $this->hasMany(DispatchOrderDetail::class, "id_dispatch_order", "id");
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, "invoice_id", "id");
    }

    public function deliveryOrder()
    {
        return $this->hasOne(DeliveryOrder::class, "dispatch_order_id", "id")
            ->where("status", "send")
            ->orderBy("updated_at", "desc");
    }

    public function deliveryOrderValid()
    {
        return $this->hasOne(DeliveryOrder::class, "dispatch_order_id", "id")->where("status", "send");
    }

    public function receivingGood()
    {
        return $this->hasOneThrough(
            ReceivingGood::class,
            DeliveryOrder::class,
            "dispatch_order_id",
            "delivery_order_id",
            "id",
            "id"
        );
    }

    public function wasReceived()
    {
        return $this->hasOneThrough(
            ReceivingGood::class,
            DeliveryOrder::class,
            "dispatch_order_id",
            "delivery_order_id",
            "id",
            "id"
        )
            ->where("receiving_goods.delivery_status", "2")
            ->where("delivery_orders.status", "send");
    }

    public function dispatchOrderFiles()
    {
        return $this->hasMany(DispatchOrderFile::class, "dispatch_orders_id", "id");
    }

    /**
     * scope for dispatch that doesn't have delivery order
     *
     * @param [type] $query
     * @return void
     */
    public function scopeHasNotDeliveryOrder($query)
    {
        return $query
            ->whereHas("invoice", function ($QQQ) {
                return $QQQ->whereHas("salesOrderOnly", function ($QQQ) {
                    return $QQQ->whereHas("dealer");
                });
            })
            ->whereDoesntHave("deliveryOrder", function ($QQQ) {
                return $QQQ->where("status", "!=", "canceled");
            });
    }

    public function scopeByDealerName($query, $dealer_name)
    {
        return $query->whereHas("invoice", function ($QQQ) use ($dealer_name) {
            return $QQQ->whereHas("salesOrderOnly", function ($QQQ) use ($dealer_name) {
                return $QQQ->whereHas("dealer", function ($QQQ) use ($dealer_name) {
                    return $QQQ->where("name", "like", "%" . $dealer_name . "%");
                });
            });
        });
    }

    public function receipt()
    {
        return $this->hasOne(ProformaReceipt::class, "id", "receipt_id")->where("receipt_for", "3");
    }

    // public function getDispatchOrderWeightAttribute($value)
    // {
    //     return !$this->promotion_good_request_id ? $this->dispatchOrderDetail()->sum("package_weight") : $this->promotionGoodRequest->promotionGood()->sum("total_weight");
    // }

    public function scopeByDealerNameDealerOwnerCustIdDistrictCityProvince($query, $seacrh)
    {
        return $query
            ->whereHas("invoice", function ($QQQ) use ($seacrh) {
                return $QQQ
                    ->whereHas("salesOrderOnly", function ($QQQ) use ($seacrh) {
                        return $QQQ
                            ->whereHas("dealerV2", function ($QQQ) use ($seacrh) {
                                return $QQQ
                                    ->withTrashed()
                                    ->where("name", "like", "%" . $seacrh . "%")
                                    ->orWhere("owner", "like", "%" . $seacrh . "%")
                                    ->orWhere("dealer_id", $seacrh)
                                    ->orWhereHas("addressDetail", function ($QQQ) use ($seacrh) {
                                        return $QQQ
                                            ->whereType("dealer")
                                            ->where(function ($QQQ) use ($seacrh) {
                                                return $QQQ
                                                    ->whereHas("province", function ($QQQ) use ($seacrh) {
                                                        return $QQQ->where("name", "like", "%" . $seacrh . "%");
                                                    })
                                                    ->orWhereHas("city", function ($QQQ) use ($seacrh) {
                                                        return $QQQ->where("name", "like", "%" . $seacrh . "%");
                                                    })
                                                    ->orWhereHas("district", function ($QQQ) use ($seacrh) {
                                                        return $QQQ->where("name", "like", "%" . $seacrh . "%");
                                                    });
                                            });
                                    });
                            });
                    });
            });
    }

    /**
     * personel branch
     */
    public function scopePersonelBranch($query, $personel_id = null)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", ($personel_id ? $personel_id : auth()->user()->personel_id))->pluck("region_id");

        /* get marketing on branch */
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);

        /* get dealer marketing on branch, to get only marketing which handle dealer today */
        $personel_dealer = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $marketing_on_branch)->pluck("personel_id");

        /* get sales order by personel */
        $sales_orders_id = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $personel_dealer)->pluck("id");

        return $query
            ->whereHas("invoice", function ($QQQ) use ($sales_orders_id) {
                return $QQQ
                    ->whereHas("salesOrderOnly")
                    ->whereIn("sales_order_id", $sales_orders_id);
            });
    }

    public function addressDelivery()
    {
        return $this->hasOne(DealerDeliveryAddress::class, "id", "delivery_address_id");
    }

    public function salesOrderDeep()
    {
        return $this->hasOneDeepFromRelations($this->invoice(), (new Invoice())->salesOrder());

    }

    public function promotionGoodRequest()
    {
        return $this->belongsTo(PromotionGoodRequest::class, "promotion_good_request_id", "id");
    }

    // public function promotionGoodRequestDispactOrder()
    // {
    //     return $this->belongsTo(PromotionGoodDispatchOrder::class, "promotion_good_request_id", "id");
    // }

    public function pickupDispatch()
    {
        return $this->morphOne(PickupOrderDispatch::class, 'pickupDispatchAble');
    }

    public function pickupOrderFile(){
        return $this->hasMany(PickupOrderFile::class, );
    }
   
    public function pickupOrder(){
        return $this->hasOneThrough(
            PickupOrder::class,
            PickupOrderDispatch::class,
            "dispatch_id",
            "id",
            "id",
            "pickup_order_id"
        );
    }

    public function pickupDispatchOriginal()
    {
        return $this->belongsTo(PickupOrderDispatch::class, 'id','dispatch_id');
    }

    public function pickupDispatchOriginals()
    {
        return $this->hasMany(PickupOrderDispatch::class, 'dispatch_id','id');
    }

    public function pickupOrderNotCanceled()
    {
        return $this->hasMany(PickupOrderDispatch::class, 'dispatch_id','id')->whereHas("pickupOrder", function($q){
            $q->where("status", "!=", "canceled");
        });
    }

    public function getCanCancelledAttribute()
    {
        $query = DB::table('pickup_order_dispatches as pod')
            ->join('pickup_orders as po', function ($join) {
                $join->on('pod.pickup_order_id', '=', 'po.id')
                     ->whereNull('po.deleted_at');
            })
            ->where('pod.dispatch_id', $this->id)
            ->where('po.status', '!=', 'canceled')
            ->whereNull('pod.deleted_at')
            ->first();

        if ($query || $this->status == "canceled") {
            return false;
        }

        return true;
    }
}
