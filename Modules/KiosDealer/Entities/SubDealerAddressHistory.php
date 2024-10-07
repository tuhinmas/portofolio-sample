<?php

namespace Modules\KiosDealer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;

class SubDealerAddressHistory extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\SubDealerAddressHistoryFactory::new();
    }

    
    public function province(){
        return $this->hasOne(Province::class, 'id','province_id');
    }
    public function city(){
        return $this->hasOne(City::class, 'id','city_id');
    }
    public function district(){
        return $this->hasOne(District::class, 'id','district_id');
    }
}
