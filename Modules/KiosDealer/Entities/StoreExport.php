<?php

namespace Modules\KiosDealer\Entities;


use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\KiosDealer\Entities\CoreFarmer;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreExport extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = "stores";
    protected $casts = [
        "id" => "string"
    ];

    protected $appends = [
        "personel_marketing_name",
        "province_name",
        "city_name",
        "district_name",
        "core_farmers",
        "address_farmer",

        

    ];

    public $incrementing = false;
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\StoreFactory::new ();
    }

    public function core_farmer()
    {
        return $this->hasMany(CoreFarmer::class, 'store_id', 'id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id')->with('position');
    }

    public function agencyLevel()
    {
        return $this->belongsTo(AgencyLevel::class, 'agency_level_id', 'id');
    }

    public function province()
    {
        return $this->hasOne(Province::class, 'id', 'province_id');
    }
    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }
    public function district()
    {
        return $this->hasOne(District::class, 'id', 'district_id');
    }

    public function telephoneReference()
    {
        return $this->hasOne(Store::class, "id", "phone_number_reference");
    }


    public function getPersonelMarketingNameAttribute()
    {
        $data = $this->personel()->first();
        if($data){
            return $data->name;
        }
    }

    public function getProvinceNameAttribute()
    {
        $data = $this->province()->first();
        if($data){
            return $data->name;
        }
    }

    public function getCityNameAttribute()
    {
        $data = $this->city()->first();
        if($data){
            return $data->name;
        }
    }

    public function getDistrictNameAttribute()
    {
        $data = $this->district()->first();
        if($data){
            return $data->name;
        }
    }

    public function getCoreFarmersAttribute()
    {

        $farmers = [];
        $getfarmer = $this->core_farmer()->get();

        foreach($getfarmer as $key => $value) {
            $farmers[] =  $value->id. ",". $value->name .",".$value->address . ",". $value->telephone;
        }

        return $farmers;
    }


}
