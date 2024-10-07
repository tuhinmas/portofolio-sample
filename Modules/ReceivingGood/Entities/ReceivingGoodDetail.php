<?php

namespace Modules\ReceivingGood\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PromotionGood\Entities\PromotionGood;

class ReceivingGoodDetail extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use Enums;

    public $incrementing = false;
    protected $enumStatuses = [
        "delivered",
        "broken",
        "incorrect",
    ];

    protected $casts = [
        "id" => "string",
    ];

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\ReceivingGood\Database\factories\ReceivingGoodDetailFactory::new ();
    }

    public function product()
    {
        return $this->hasOne(Product::class, "id", "product_id");
    }

    public function receivingGood()
    {
        return $this->belongsTo(ReceivingGood::class, "receiving_good_id", "id");
    }

    public function receivingGoodHasReceived()
    {
        return $this->belongsTo(ReceivingGood::class, "id", "receiving_good_id")->where("delivery_status", "2");
    }

    public function dispatchOrder()
    {
        return $this->hasOneDeepFromRelations($this->receivingGood(), (new ReceivingGood())->invoiceHasOne());
    }
   
    public function invoice()
    {
        return $this->hasOneDeepFromRelations($this->dispatchOrder(), (new DispatchOrder())->invoice());
    }

    public function promotionGood()
    {
        return $this->hasOne(PromotionGood::class, "id", "promotion_good_id");
    }
}
