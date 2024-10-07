<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\ActivityTrait;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Spatie\Activitylog\Traits\LogsActivity;

class DeliveryPickupOrder extends Model
{
    use HasFactory, Uuids, SoftDeletes,  ActivityTrait, LogsActivity;

    protected $guarded = [];

    public function pickupOrder()
    {
        return $this->hasOne(PickupOrder::class, "id", "pickup_order_id");
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id','id');
    }
}
