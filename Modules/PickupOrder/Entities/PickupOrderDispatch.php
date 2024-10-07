<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\ActivityTrait;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Spatie\Activitylog\Traits\LogsActivity;

class PickupOrderDispatch extends Model
{
    use HasFactory, Uuids, SoftDeletes, ActivityTrait, LogsActivity;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\PickupOrderDispatchFactory::new ();
    }

    public function pickupDispatchAble()
    {
        return $this->morphTo(__FUNCTION__, 'dispatch_type', 'dispatch_id');
    }

    public function pickupOrder()
    {
        return $this->belongsTo(PickupOrder::class);
    }

    public function dispatch()
    {
        // if ($this->dispatchOrder()->exists()) {
        //     return $this->dispatchOrder();
        // }

        // if ($this->dispatchPromotion()->exists()) {
        //     return $this->dispatchPromotion();
        // }

        return $this->pickupDispatchAble();
    }

    public function dispatchOrder()
    {
        return $this->belongsTo(DispatchOrder::class, "dispatch_id", "id");
    }

    public function dispatchPromotion()
    {
        return $this->belongsTo(DispatchPromotion::class, "dispatch_id", "id");
    }
}
