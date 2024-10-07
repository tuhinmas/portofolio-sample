<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\ActivityTrait;
use App\Traits\Enums;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Entities\Porter;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Modules\DataAcuan\Entities\Warehouse;
use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Entities\PickupOrderFile;
use Spatie\Activitylog\Traits\LogsActivity;

class PickupOrder extends Model
{
    use HasFactory, Uuids, SoftDeletes, ActivityTrait, LogsActivity;
    use Enums;

    protected $guarded = [];
    protected $enumStatuses = [
        'planned',
        'loaded',
        'delivered',
        'canceled',
        'revised',
        'failed',
        'checked'
    ];

    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\PickupOrderFactory::new ();
    }

    public function deliveryPickupOrders()
    {
        return $this->hasMany(DeliveryPickupOrder::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function pickupOrderDetails()
    {
        return $this->hasMany(PickupOrderDetail::class)
            ->where("pickup_type", "load");
    }

    public function pickupOrderDetailLoadDirect()
    {
        return $this->hasMany(PickupOrderDetail::class)
            ->where("pickup_type", "load")
            ->where("detail_type", "dispatch_order");
    }

    public function pickupOrderDetailLoadPromotion()
    {
        return $this->hasMany(PickupOrderDetail::class)
            ->where("pickup_type", "load")
            ->where("detail_type", "dispatch_promotion");
    }

    public function pickupOrderDetailUnloads()
    {
        return $this->hasMany(PickupOrderDetail::class)
            ->where("pickup_type", "unload");
    }

    public function armada()
    {
        return $this->belongsTo(Driver::class, "driver_id", "id");
    }

    public function pickupOrderDispatch()
    {
        return $this->hasMany(PickupOrderDispatch::class, "pickup_order_id", "id");
    }

    public function pickupOrderFiles()
    {
        return $this->hasMany(PickupOrderFile::class, "pickup_order_id", "id");
    }

    public function pickupOrderFileMandatories()
    {
        return $this->hasMany(PickupOrderFile::class, "pickup_order_id", "id")
            ->whereIn("caption", mandatory_captions());
    }

    public function pickupLoadHistories()
    {
        return $this->hasMany(PickupLoadHistory::class, "pickup_order_id", "id")
            ->where("status", "created");
    }

    public function pickupUnloadHistories()
    {
        return $this->hasMany(PickupLoadHistory::class, "pickup_order_id", "id")
            ->where("status", "canceled");
    }

    public function proformaReceipt()
    {
        return $this->hasOne(ProformaReceipt::class, "id", "receipt_id");
    }

    public function porter()
    {
        return $this->hasOne(Porter::class, "warehouse_id", "warehouse_id");
    }

    /**
     *
     *
     * @param [type] $query
     * @param [type] $parameter
     * @return void
     */
    public function scopeByWarehouse($query, $parameter)
    {
        return $query->whereHas("warehouse", function ($QQQ) use ($parameter) {
            return $QQQ
                ->where("name", "like", "%" . $parameter . "%")
                ->orWhere("code", "like", "%" . $parameter . "%");
        });
    }

    public function scopeByArmada($query, $parameter)
    {
        return $query->where(function ($QQQ) use ($parameter) {
            return $QQQ
                ->whereHas("armada", function ($QQQ) use ($parameter) {
                    return $QQQ
                        ->where("police_number", "like", "%" . $parameter . "%")
                        ->orWhere("transportation_type", "like", "%" . $parameter . "%");
                })
                ->orWhere("type_driver", $parameter);
        });
    }

    public function scopeToday($query)
    {
        return $query->whereDate("delivery_date", now()->format("Y-m-d"));
    }

    public function scopeDeliveryOrderNumber($query, $delivery_order_number)
    {
        return $query
            ->whereHas("pickupOrderDispatch", function ($QQQ) use ($delivery_order_number) {
                return $QQQ
                    ->whereHas("dispatch", function ($QQQ) use ($delivery_order_number) {
                        return $QQQ->whereHas("deliveryOrder", function ($QQQ) use ($delivery_order_number) {
                            return $QQQ->where("delivery_order_number", $delivery_order_number);
                        });
                    });
                // ->where(function($QQQ){
                //     return $QQQ->where("dispatch_type", "dispatch_order");
                // })
                // ->orWhere();
            });
    }

    public function scopeByPorter($query, $parameter)
    {
        return $query
            ->whereHas("warehouse")
            ->whereHas("porter", function ($QQQ) use ($parameter) {
                return $QQQ->where("personel_id", $parameter);
            });
    }

    public function porters()
    {
        return $this->hasMany(Porter::class, "warehouse_id", "warehouse_id");
    }
}
