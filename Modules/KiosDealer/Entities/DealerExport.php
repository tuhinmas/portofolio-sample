<?php

namespace Modules\KiosDealer\Entities;

use Modules\Address\Entities\Address;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Modules\DataAcuan\Entities\Entity;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class DealerExport extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = "dealers";
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
        "agency_level_name",
        "entity_level_name",
        "region_dealer",
        "dealer_has_payment",
        "sub_region_dealer"

    ];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerExportFactory::new();
    }

    public function personel(){
        return $this->hasOne(Personel::class, 'id', 'personel_id');
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

    public function getRegionDealerAttribute()
    {
        $region = $this->regionHasOne()->where("address_with_details.type", "dealer")->with("subRegion", "subRegion.region.personel")->first();

        // dd($region);
        // if($region->subRegion !== null) {
            // echo "as";
            return $region ? $region->subRegion->Region->name : "-";
        // } else {
        //     return "-";
        // }
       
    }

    public function getSubRegionDealerAttribute()
    {
        $region = $this->regionHasOne()->where("address_with_details.type", "dealer")->with("subRegion", "subRegion.region.personel")->first();

        // dd($region);
        // if($region->subRegion !== null) {
            // echo "as";
            return $region ? $region->subRegion->name : "-";
        // } else {
        //     return "-";
        // }
       
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
        return $this->hasMany(Address::class, "parent_id","id")->with('district', 'city', 'province');
    }

    public function getDistrictIdAttribute($query){
         $data = $this->addressDealer()->where("type", "dealer")->first();
         if ($data) {
             return $data->district_id;
         }
    }

    public function getDistrictNameAttribute($query){
        $data = $this->addressDealer()->where("type", "dealer")->first();
        if ($data) {
            return $data->district->name;
        }
    }
    
    public function getCityIdAttribute($query){
         $data = $this->addressDealer()->where("type", "dealer")->first();
         if ($data) {
             return $data->city_id;
         }
    }

    public function getCityNameAttribute($query){
        $data = $this->addressDealer()->where("type", "dealer")->first();
        if ($data) {
            return $data->city->name;
        }
    }
   
    public function getProvinceIdAttribute($query){
         $data = $this->addressDealer()->where("type", "dealer")->first();
         if ($data) {
             return $data->province_id;
         }
    }

    public function getProvinceNameAttribute($query){
        $data = $this->addressDealer()->where("type", "dealer")->first();
        if ($data) {
            return $data->province->name;
        }
    }
   
    public function getOwnerDistrictIdAttribute($query){
         $data = $this->addressDealer()->where("type", "dealer_owner")->first();
         if ($data) {
             return $data->district_id;
         }
    }

    public function getOwnerDistrictNameAttribute($query){
        $data = $this->addressDealer()->where("type", "dealer_owner")->first();
        if ($data) {
            return $data->district->name;
        }
    }
    
    public function getOwnerCityIdAttribute($query){
         $data = $this->addressDealer()->where("type", "dealer_owner")->first();
         if ($data) {
             return $data->city_id;
         }
    }

    public function getOwnerCityNameAttribute($query){
        $data = $this->addressDealer()->where("type", "dealer_owner")->first();
        if ($data) {
            return $data->city->name;
        }
    }
   
    public function getOwnerProvinceIdAttribute($query){
         $data = $this->addressDealer()->where("type", "dealer_owner")->first();
         if ($data) {
             return $data->province_id;
         }
    }

    public function getOwnerProvinceNameAttribute($query){
        $data = $this->addressDealer()->where("type", "dealer_owner")->first();
        if ($data) {
            return $data->province->name;
        }
    }

    public function salesOrderOnly()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id')
            ->where("model", "1")->where("status", "confirmed")
            ->where("invoiceOnly", "!=", null);
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

    public function getCustomCreditLimitAttribute()
    {
        $limit = 0;
        $dealer_grading = $this->dealerGrading()->first();
        if ($dealer_grading) {
            if ($dealer_grading->custom_credit_limit > 0) {
                $limit = $dealer_grading->custom_credit_limit;
            }
        }
        return $limit;
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

    public function dealerGrading()
    {
        return $this->hasMany(DealerGrading::class, "dealer_id", "id")->latest();
    }

    public function allPayment()
    {
        try {
            $payments_method = [];
            $payments = PaymentMethod::orderBy("name")->get();

            foreach($payments as $key => $value) {
                $payments_method[] =  $value->name . " ,". $value->days;
            }

            return $payments_method;
            // return $payments[0]->name . " ,". $payments[0]->days;
        } catch (\Throwable$th) {
            throw $th;
        }
    }

    public function dealerWithPayment()
    {
        return $this->hasMany(DealerPaymentMethod::class, "dealer_id", "id")->with("paymentMethod");
    }

    public function getDealerHasPaymentAttribute()
    {

        $payments_method = [];
        //    $payments = PaymentMethod::orderBy("name")->get();

            
        $payments = $this->dealerWithPayment()->get();
        $grading = $this->grading()->first();
        $grading_name = "Hitam";
        if ($grading) {
            $grading_name = $grading->name;
        }
        if (count($payments) != 0) {
            foreach($payments as $key => $value) {
                $payments_method[] =  $value->name . " ,". $value->days;
            }
        } else if (count($payments) == 0 && $grading_name == "Hitam") {
            foreach($payments as $key => $value) {
                $payments_method[] =  $value->name . " ,". $value->days;
            }
        } else {
            return $this->allPayment();
        }

        return $payments_method;
    }



}
