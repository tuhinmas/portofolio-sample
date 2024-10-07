<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use App\Models\ActivityLog;
use App\Traits\FilterByArea;
use App\Traits\CapitalizeText;
use App\Traits\SupervisorCheck;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\DataAcuan\Entities\Bank;
use Modules\DataAcuan\Entities\Entity;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\Personel\Entities\Personel;
use Modules\Address\Entities\AddressTemp;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealer\Entities\Handover;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\KiosDealerV2\Entities\DealerV2;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\KiosDealerV2\Entities\SubDealerV2;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DealerTemp extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;
    use SupervisorCheck;
    use SuperVisorCheckV2;
    use FilterByArea;
    use LogsActivity;
    use Enums;
    use CapitalizeText;

    protected $guarded = [];
    protected $table = "dealer_temps";
    protected $casts = [
        "id" => "string",
    ];
    protected $enumStatuses = [
        "draft",
        "revised",
        "revised change",
        "filed",
        "submission of changes",
        "filed rejected",
        "change rejected",
        "wait approval",
    ];

    public $incrementing = false;
    protected $appends = ["prefix_id"];

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
        return $query->where('name', 'like', "%" . $name . "%");
    }

    /**
     * Scope a query to only include popular owner.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOwner($query, $owner)
    {
        return $query->where('owner', 'like', "%" . $owner . "%");
    }

    /**
     * Scope a query to only include popular owner.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNameOrOwner($query, $param)
    {
        return $query->where('owner', 'like', "%" . $param . "%")->orWhere('name', 'like', "%" . $param . "%")->orWhere('id', 'like', "%" . $param . "%")->orWhere('address', 'like', "%" . $param . "%");
    }

    public function scopeWhereNameOrOwnerOrDealerId($query, $param)
    {
        return $query
            ->where(function ($QQQ) use ($param) {
                return $QQQ
                    ->where('name', 'like', "%" . $param . "%")
                    ->orWhere("owner", 'like', "%" . $param . "%")
                    ->orWhereHas("dealerFix", function ($query) use ($param) {
                        return $query->where("dealer_id", 'like', "%" . $param . "%");
                    });
            });
    }

    public function scopeDealerConfirmation($query, $count)
    {
        return $query->withCount("dealer_file")->having("dealer_file_count", ">=", $count);
    }

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerTempFactory::new();
    }

    public function adress_detail()
    {
        return $this->hasMany(AddressTemp::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function addressDetail()
    {
        return $this->hasMany(AddressTemp::class, "parent_id", "id");
    }

    public function dealerTempNote()
    {
        return $this->hasMany(DealerTempNote::class, "dealer_temp_id", "id");
    }

    public function dealerTempNoteLast()
    {
        return $this->hasOne(DealerTempNote::class, "dealer_temp_id", "id")->orderBy("created_at", "desc");
    }

    public function getPrefixIdAttribute()
    {
        return config("app.dealer_id_prefix");
    }

    public function dealer_file()
    {
        return $this->hasMany(DealerFileTemp::class, 'dealer_id', 'id');
    }
 
    public function dealerFile()
    {
        return $this->hasMany(DealerFileTemp::class, 'dealer_id', 'id');
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id');
    }

    public function agencyLevel()
    {
        return $this->belongsTo(AgencyLevel::class, 'agency_level_id', 'id');
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    public function dealer_file_confirmation()
    {
        return $this->hasMany(DealerFileTemp::class, 'dealer_id', 'id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'id', 'handover_status');
    }

    public function statusFee()
    {
        return $this->hasOne(StatusFee::class, 'id', 'status_fee');
    }

    public function scopeSupervisor($query)
    {
        $personel_id = $this->getPersonel();
        return $query->whereIn("personel_id", $personel_id);
    }

    public function dealerFix()
    {
        return $this->hasOne(DealerV2::class, "id", "dealer_id");
    }

    public function subDealerFix()
    {
        return $this->hasOne(SubDealerV2::class, "id", "sub_dealer_id");
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

    public function dealerBank()
    {
        return $this->hasOne(Bank::class, "id", "bank_id");
    }

    public function ownerBank()
    {
        return $this->hasOne(Bank::class, "id", "owner_bank_id");
    }

    public function dealerChangeHistory()
    {
        return $this->hasOne(DealerChangeHistory::class, "dealer_temp_id", "id");
    }

    public function logConfirmation()
    {
        return $this->hasMany(ActivityLog::class, "subject_id", "id")
            ->where("description", "updated")
            ->whereIn("properties->attributes->status", ["filed rejected", "change rejected", "wait approval"])
            ->orderBy("created_at", "desc")
            ->select(DB::raw("activity_log.*, JSON_UNQUOTE(json_extract(activity_log.properties, '$.attributes.status')) as status_chenge"));
    }

    public function submitedBy()
    {
        return $this->belongsTo(Personel::class, "submited_by", "id");
    }

    public function scopeFilterStatusDealer($QQQ, $status = [])
    {
        // enum('draft','filed','submission of changes','filed rejected','change rejected','wait approval','revised','revised change')
        $statuses = [
            "1" => "draft",
            "2" => "filed",
            "3" => "submission of changes",
            "4" => "filed rejected",
            "5" => "change rejected",
            "6" => "wait approval",
            "7" => "revised",
            "8" => "revised change"
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
                $subquery->where('status', "draft")->whereNotNull('dealer_id');
            })->orWhere(function ($subquery) use ($arr) {
                $subquery->whereIn('status', $arr);
            });
        } else {
            return $QQQ->whereIn('status', $filteredStatuses);
        }
    }
}
