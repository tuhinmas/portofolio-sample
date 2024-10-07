<?php

namespace App\Models;

use App\Traits\Uuids;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $guarded = [];
    
    public function organisation(){
        return $this->belongsTo(Organisation::class,'id','parent_id');
    }

    public function personel(){
        return $this->belongsTo(Personel::class,'id','parent_id');
    }
    
    public function country(){
        return $this->hasOne(Country::class,'id','country_id');
    }

    public function province(){
        return $this->hasOne(Province::class, "id", "province_id");
    }
    public function city(){
        return $this->hasOne(City::class, "id", "city_id");
    }
    public function district(){
        return $this->hasOne(District::class, "id", "district_id");
    }
}
