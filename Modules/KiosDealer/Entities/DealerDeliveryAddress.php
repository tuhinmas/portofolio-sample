<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\CapitalizeText;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\KiosDealer\Entities\Dealer;

class DealerDeliveryAddress extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Uuids;
    use CapitalizeText;

    protected $guarded = [];
    public $appends = [
        "is_used",
    ];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerDeliveryAddressFactory::new ();
    }

    public function getIsUsedAttribute()
    {
        return $this->dispatchOrders()->first() ? 1 : 0;
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, "dealer_id", "id");
    }

    public function province()
    {
        return $this->belongsTo(Province::class, "province_id", "id");
    }

    public function district()
    {
        return $this->belongsTo(District::class, "district_id", "id");
    }

    public function city()
    {
        return $this->belongsTo(City::class, "city_id", "id");
    }


    public function dispatchOrders()
    {
        return $this->hasMany(DispatchOrder::class, "delivery_address_id", "id");
    }

    // public function dispatchOrders()
    // {
    //     return $this->belongsToMany(DispatchOrder::class, "delivery_address_id", "id");
    // }

}
