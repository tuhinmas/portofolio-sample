<?php

namespace Modules\KiosDealer\Entities;

use Modules\Address\Entities\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Entity;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\Personel\Entities\Personel;

class SubDealerExport extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = "sub_dealers";
    protected $casts = [
        "id" => "string"
    ];
    protected $appends = [
        "personel_marketing_name",
        "grading_name",
        "district_id",
        "district_name",
        "city_id",
        "city_name",
        "province_id",
        "province_name",
        "owner_district_id",
        "owner_district_name",
        "owner_city_id",
        "owner_city_name",
        "owner_province_id",
        "owner_province_name",
        "region_dealer",
        "sub_region_dealer"
    ];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\SubDealerExportFactory::new();
    }

    public function personel(){
        return $this->hasOne(Personel::class, 'id', 'personel_id');
    }

    public function getRegionDealerAttribute()
    {
        $region = $this->regionHasOne()->where("address_with_details.type", "sub_dealer")->with("subRegion", "subRegion.region.personel")->first();

        return $region ? $region->subRegion->Region->name : "-";
   
    }

    public function getSubRegionDealerAttribute()
    {
        $region = $this->regionHasOne()->where("address_with_details.type", "sub_dealer")->with("subRegion", "subRegion.region.personel")->first();

        return $region ? $region->subRegion->name : "-";
       
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

    public function getPersonelMarketingNameAttribute()
    {
        $data = $this->personel()->first();
        if($data){
            return $data->name;
        }
    }

    public function getGradingNameAttribute()
    {
        $data = $this->grading()->first();
        if($data){
            return $data->name;
        }
    }

    public function addressDealer(){
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function getDistrictIdAttribute($query){
         $data = $this->addressDealer()->where("type", "sub_dealer")->first();
         if ($data) {
             return $data->district_id;
         }
    }

    public function getDistrictNameAttribute($query){
        $data = $this->addressDealer()->where("type", "sub_dealer")->first();
        if ($data) {
            return $data->district->name;
        }
    }
    
    public function getCityIdAttribute($query){
         $data = $this->addressDealer()->where("type", "sub_dealer")->first();
         if ($data) {
             return $data->city_id;
         }
    }

    public function getCityNameAttribute($query){
        $data = $this->addressDealer()->where("type", "sub_dealer")->first();
        if ($data) {
            return $data->city->name;
        }
    }
   
    public function getProvinceIdAttribute($query){
         $data = $this->addressDealer()->where("type", "sub_dealer")->first();
         if ($data) {
             return $data->province_id;
         }
    }

    public function getProvinceNameAttribute($query){
        $data = $this->addressDealer()->where("type", "sub_dealer")->first();
        if ($data) {
            return $data->province->name;
        }
    }
   
    public function getOwnerDistrictIdAttribute($query){
         $data = $this->addressDealer()->where("type", "sub_dealer_owner")->first();
         if ($data) {
             return $data->district_id;
         }
    }

    public function getOwnerDistrictNameAttribute($query){
        $data = $this->addressDealer()->where("type", "sub_dealer_owner")->first();
        if ($data) {
            return $data->district->name;
        }
    }
    
    public function getOwnerCityIdAttribute($query){
         $data = $this->addressDealer()->where("type", "sub_dealer_owner")->first();
         if ($data) {
             return $data->city_id;
         }
    }

    public function getOwnerCityNameAttribute($query){
        $data = $this->addressDealer()->where("type", "sub_dealer_owner")->first();
        if ($data) {
            return $data->city->name;
        }
    }
   
    public function getOwnerProvinceIdAttribute($query){
         $data = $this->addressDealer()->where("type", "sub_dealer_owner")->first();
         if ($data) {
             return $data->province_id;
         }
    }

    public function getOwnerProvinceNameAttribute($query){
        $data = $this->addressDealer()->where("type", "sub_dealer_owner")->first();
        if ($data) {
            return $data->province->name;
        }
    }

    public function agencyLevel()
    {
        return $this->hasOne(AgencyLevel::class, 'id', 'agency_level_id');
    }

    public function getAgencyLevelNameAttribute($query){
        $data = $this->agencyLevel()->first();
        if ($data) {
            return $data->name;
        }
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    public function getEntityLevelNameAttribute()
    {
        $data = $this->entity()->first();
        if ($data) {
            return $data->name;
        }
    }

    public function subRegionHasOne()
    {
        return $this->hasOneThrough(
            MarketingAreaDistrict::class,
            Address::class,
            "parent_id",
            "district_id", // foreign key on address
            "id", // local key on dealers
            "district_id" //Local key on the Address

            /* see on https://laravel.com/docs/8.x/eloquent-relationships#has-many-through
         * for detail
         */
        );
    }

    public function regionHasOne()
    {
        return $this->hasOneThrough(
            MarketingAreaDistrict::class,
            Address::class,
            "parent_id",
            "district_id", // foreign key on address
            "id", // local key on dealers
            "district_id" //Local key on the Address

            /* see on https://laravel.com/docs/8.x/eloquent-relationships#has-many-through
         * for detail
         */
        );
    }


}
