<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use App\Models\ActivityLog;
use App\Traits\MarketingArea;
use App\Traits\CapitalizeText;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\DataAcuan\Entities\Entity;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\Personel\Entities\Personel;
use Modules\Address\Entities\AddressTemp;
use Modules\KiosDealer\Entities\Handover;
use Modules\KiosDealer\Entities\SubDealer;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\KiosDealer\Entities\SubDealerChangeHistory;

class SubDealerTemp extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use MarketingArea;
    use SuperVisorCheckV2;
    use CapitalizeText;

    protected $guarded = [];
    protected $table = "sub_dealer_temps";
    protected $casts = [
        "id" => "string"
    ];
    protected $enumStatuses = [
        "draft",
        "filed",
        "submission of changes",
        "filed rejected",
        "change rejected",
        "wait approval",
        "revised",
        "revised change"
    ];

    public $incrementing = false;


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
    
      /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'like', "%".$name."%")->orWhere("owner", "like",  "%".$name."%")
        ->orWhereHas('subDealerFix', function ($query) use ($name) {
           return $query->where('sub_dealer_id', 'like', '%' . $name . '%');
        })
        ->orWhere('address', 'like', "%".$name."%");
    }

    public function scopeSubDealerConfirmation($query, $count)
    {
        return $query->withCount("subDealerFile")->having("sub_dealer_file_count", ">=", $count);
    }
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\SubDealerTempFactory::new();
    }

    public function subDealerFile()
    {
        return $this->hasMany(DealerFileTemp::class, 'dealer_id', 'id');
    }

    public function subDealerTempNote()
    {
        return $this->hasMany(SubDealerTempNote::class, "sub_dealer_temp_id", "id");
    }

    public function subDealerTempNoteLast()
    {
        return $this->hasOne(SubDealerTempNote::class, "sub_dealer_temp_id", "id")->orderByDesc("created_at");
    }

    public function adressDetail(){
        return $this->hasMany(AddressTemp::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function addressDetail(){
        return $this->hasMany(AddressTemp::class, "parent_id", "id");
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id');
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'id', 'handover_status');
    }

    public function subDealerChangeHistory()
    {
        return $this->hasOne(SubDealerChangeHistory::class, "sub_dealer_temp_id", "id");
    }

    public function scopeSupervisor($query)
    {
        $personel_id = $this->getPersonel();
        return $query->whereIn("personel_id", $personel_id);
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    public function subDealerFix(){
        return $this->hasOne(SubDealer::class, "id", "sub_dealer_id");
    }

    public function subDealerAddress()
    {
        return $this->adressDetail()->where("type", "sub_dealer")->with('marketingAreaDistrict');
    }

    public function storeFix()
    {
        return $this->hasOne(Store::class, "id", "store_id");
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
        $personel_id_on_region = DB::table('marketing_area_regions')->where("id", $region_id)->get()->pluck("personel_id");
        $personel_id = $this->getPersonelChild($personel_id_on_region);
        return $QQQ->whereIn("personel_id", $personel_id);
    }

    public function logConfirmation()
    {
        return $this->hasMany(ActivityLog::class, "subject_id", "id")
            ->where("description", "updated")
            ->whereIn("properties->attributes->status", ["filed rejected", "change rejected", "wait approval"])
            ->orderBy("created_at", "desc")
            ->select(DB::raw("activity_log.*, JSON_UNQUOTE(json_extract(activity_log.properties, '$.attributes.status')) as status_chenge"));
    }

    public function submitedBy(){
        return $this->belongsTo(Personel::class, "submited_by", "id");
    }

    public function scopeFilterStatusSubDealer($QQQ, $status = [])
    {
        // enum('draft','filed','submission of changes','filed rejected','change rejected','revised','revised change')
        $statuses = [
            "1" => "draft",
            "2" => "filed",
            "3" => "submission of changes",
            "4" => "filed rejected",
            "5" => "change rejected",
            "6" => "revised",
            "7" => "revised change"
        ];

        // Membuat array untuk menyimpan status yang sesuai dengan filter
        $filteredStatuses = [];

        foreach ($statuses as $key => $value) {
            if (in_array($key, $status)) {
                $filteredStatuses[] = $value;
            }
        }

        if (in_array("1", $status)) {

            $arr = array_diff($filteredStatuses, array('draft'));

            return $QQQ->where(function ($subquery) use ($filteredStatuses) {
                $subquery->where('sub_dealer_temps.status', "draft")->whereNotNull('sub_dealer_temps.sub_dealer_id');
            })->orWhere(function ($subquery) use ($arr) {
                $subquery->whereIn('sub_dealer_temps.status', $arr);
            });
        } else {
            return $QQQ->whereIn('status', $filteredStatuses);
        }
    }
}
