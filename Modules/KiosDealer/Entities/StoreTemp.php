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
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\AgencyLevel;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\KiosDealer\Entities\CoreFarmerTemp;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreTemp extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use MarketingArea;
    use SuperVisorCheckV2;
    use CascadeSoftDeletes;
    use CapitalizeText;

    public $incrementing = false;
    protected $cascadeDeletes = ['core_farmer'];
    protected $dates = ['deleted_at'];
    protected $table = "store_temps";
    protected $guarded = [];

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
    
    public function scopeWhereName($query, $name)
    {
        return $query->where("name", "like", $name);
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function scopeCountFarmer($query, $count)
    {
        return $query->withCount("core_farmer")->having("core_farmer_count", ">=", $count);
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\StoreTempFactory::new ();
    }

    public function core_farmer()
    {
        return $this->hasMany(CoreFarmerTemp::class, 'store_temp_id', 'id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id')->with("position");
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

    public function telephoneReference(){
        return $this->hasOne(Store::class,"id", "phone_number_reference");
    }

    public function scopeSupervisor($query)
    {
        $personel_id = $this->getPersonel();
        return $query->whereIn("personel_id", $personel_id);
    }

    public function marketingAreaDistrict()
    {
        return $this->hasOne(MarketingAreaDistrict::class, 'district_id', 'district_id')->with('subRegion');
    }

    public function log(){
        return $this->hasMany(ActivityLog::class, "subject_id","id");
    }

    /**
     * change log
     *
     * @return void
     */
    public function logConfirmation()
    {
        return $this->hasMany(ActivityLog::class, "subject_id", "id")
            ->where("description", "updated")
            ->whereIn("properties->attributes->status", ["filed rejected", "change rejected", "wait approval"])
            ->orderBy("created_at", "desc")
            ->select(DB::raw("activity_log.*, JSON_UNQUOTE(json_extract(activity_log.properties, '$.attributes.status')) as status_chenge"));
    }
}
