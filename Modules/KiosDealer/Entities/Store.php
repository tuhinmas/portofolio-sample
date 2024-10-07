<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use App\Models\ActivityLog;
use App\Traits\CapitalizeText;
use App\Traits\MarketingArea;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\City;
use Spatie\Activitylog\LogOptions;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
// use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\KiosDealer\Entities\CoreFarmer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Voucher\Entities\DiscountVoucher;

class Store extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use SuperVisorCheckV2;
    use MarketingArea;
    use CapitalizeText;

    public $incrementing = false;
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\StoreFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }

    // public function OnlyDistrictStore()
    // {
    //     return $this->districtListMarketing($this->id);
    // }

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

    public function scopeSupervisor($query)
    {
        $personel_id = $this->getPersonel();
        $user_position = DB::table('positions')->whereNull("deleted_at")->where("name", auth()->user()->profile->position->name)->first();
        $positions = [
            "Marketing Manager (MM)",
            "Marketing Support",
            "Operational Manager",
            'Support Bagian Distributor',
            'Support Distributor',
            'Support Bagian Kegiatan',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
            'User Jember'
        ];

        if (in_array($user_position->name, $positions)) {
            return $query->whereNotNull("personel_id");
        } else {
            return $query->whereIn("personel_id", $personel_id)->whereNotNull("personel_id");
        }
    }

    public function coreFarmerHasMany()
    {
        // DB::enableQueryLog(); // Enable query log
        return $this->hasManyThrough(
            CoreFarmer::class,
            Store::class,
            'personel_id',
            'store_id',
            'id',
            'id'
        );
    }

        /**
     * filter dealer by region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */
    public function scopeRegion($QQQ, $region_id)
    {
        $district_list_on_region = $this->districtListByAreaId($region_id);
        return $QQQ->whereIn("district_id", $district_list_on_region);
    }

    /**
     * filter dealer by region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */
    public function scopeSubRegion($QQQ, $region_id)
    {
        $district_list_on_region = $this->districtListByAreaId($region_id);
        return $QQQ->whereIn("district_id", $district_list_on_region);
    }

    public function scopeDistrict($QQQ, $district_id)
    {
        $district_list_on_district = $this->districtListById($district_id);
        return $QQQ->whereIn("district_id", $district_list_on_district);
    }

    public function log(){
        return $this->hasMany(ActivityLog::class, "subject_id","id");
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    public function storeTemp(){
        return $this->hasMany(StoreTemp::class, "store_id","id");
    }

    public function getIsEditableAttribute()
    {
        return $this->kiosTempAccepted($this);
    }

    public function getIsTransferableAttribute()
    {
        $district = MarketingAreaDistrict::where('personel_id', $this->personel_id)
            ->where('district_id', $this->district_id)
            ->first();

        if ($district && $this->status == 'accepted') {
            return true;
        }elseif ($this->dealer_id == null && $this->sub_dealer_id == null) {
            return true;
        }

        return false;
    }

    private function kiosTempAccepted($store)
    {
        $dealer = DealerTemp::where('store_id', $store->id)->whereNotIn('status', ['filed rejected','change rejected'])->first();
        $subDealer = SubDealerTemp::where('store_id', $store->id)->whereNotIn('status', ['filed rejected','change rejected'])->first();

        if ($store->dealer_id != null || $store->sub_dealer_id != null || $dealer || $subDealer) {
            return false;
        }
        
        return true;
    }

    public function dealerTemp()
    {
        return $this
            ->hasOne(DealerTemp::class, "store_id", "id")
            ->whereNotIn("status", ["filed rejected", "change rejected"]);
    }


    public function subDealerTemp()
    {
        return $this
            ->hasOne(SubDealerTemp::class, "store_id", "id")
            ->whereNotIn("status", ["filed rejected", "change rejected"]);
    }

    
    public function stores()
    {
        return $this->hasMany(DiscountVoucher::class, 'store_id');
    }
}
