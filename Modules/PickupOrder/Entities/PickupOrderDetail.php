<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;

class PickupOrderDetail extends Model
{
    use HasFactory, Uuids, SoftDeletes, ActivityTrait, LogsActivity;
    use Enums;

    protected $enumPickupTypes = ["load", "unload"];
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\PickupOrderDetailFactory::new ();
    }
    public function pickupOrderDetailFiles()
    {
        return $this->hasMany(PickupOrderDetailFile::class, "pickup_order_detail_id", "id");
    }

    public function product()
    {
        return $this->hasOne(Product::class, "id", "product_id");
    }
}
