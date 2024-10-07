<?php

namespace Modules\Personel\Entities;

use Carbon\Carbon;
use App\Traits\Enums;
use App\Traits\Uuids;
use App\Models\Contact;
use App\Models\ActivityLog;
use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Traits\CapitalizeText;
use App\Traits\SuperVisorCheckV2;
use App\Traits\SelfReferenceTrait;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\DataAcuan\Entities\Bank;
use Modules\Invoice\Entities\Invoice;
use Modules\DataAcuan\Entities\Region;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\DataAcuan\Entities\Country;
use Modules\ForeCast\Entities\ForeCast;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Notifications\Notifiable;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Religion;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Traits\ScopePersonel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\PersonelBank;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Modules\DataAcuan\Entities\IdentityCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organisation\Entities\Organisation;
use Modules\PersonelBranch\Entities\PersonelBranch;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Entities\UserAccessHistory;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\Personel\Entities\Traits\CustomPersonnelLogic;
use Modules\MarketingStatusChangeLog\Entities\MarketingStatusChangeLog;

class Personel extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use SelfReferenceTrait;
    use SuperVisorCheckV2;
    use ScopePersonel;
    use MarketingArea;
    use ScopePersonel;
    use ChildrenList;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Notifiable;
    use Uuids;
    use Enums;
    use CustomPersonnelLogic;
    use CapitalizeText;

    protected $casts = [
        "id" => "string",
    ];
    public $incrementing = false;
    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
        "deleted_at",
    ];
    protected $enumStatuses = [
        1, 2, 3,
    ];

    protected $appends = [
        "target",
        //    "persentase_achivement"
    ];

    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\PersonelFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function address()
    {
        return $this->hasMany('App\Models\Address', 'parent_id', 'id')->with('country', 'province', 'city', 'district');
    }

    public function bank()
    {
        return $this->belongsToMany(Bank::class, 'bank_personels');
    }
    public function personelUnder()
    {
        return $this->hasMany(Personel::class, 'supervisor_id', 'id');
    }

    public function personelUnderV2()
    {
        return $this->hasMany(Personel::class, 'supervisor_id', 'id')->with("personelUnderV2");
    }

    public function contact()
    {
        return $this->hasMany(Contact::class, 'parent_id', 'id');
    }

    public function contactLast()
    {
        return $this->hasOne(Contact::class, 'parent_id', 'id')->orderBy("created_at", "desc");
    }

    public function marketingStatusChangeLog()
    {
        return $this->hasMany(MarketingStatusChangeLog::class, 'personel_id', 'id');
    }

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    public function personelStatusHistory()
    {
        return $this->hasOne(PersonelStatusHistory::class, 'personel_id', 'id')->orderByDesc("created_at");
    }

    public function previousStatus()
    {
        return $this->hasOne(PersonelStatusHistory::class, 'personel_id', 'id')
            ->orderByDesc("start_date")
            ->orderByDesc("created_at");
    }

    public function supervisor()
    {
        return $this->hasOne(Personel::class, 'id', 'supervisor_id');
    }

    public function supervisorInConfirmedOrder()
    {
        return $this->hasOne(ActivityLog::class, "subject_id", "id")
            ->whereYear("created_at", now()->format("Y"))
            ->whereRaw("quarter(created_at)= ?", now()->quarter)
            ->orderBy("created_at");
    }

    public function organisation()
    {
        return $this->hasOne(Organisation::class, 'id', 'organisation_id');
    }
    public function citizenship()
    {
        return $this->hasOne(Country::class, 'id', 'citizenship');
    }
    public function bankPersonel()
    {
        return $this->belongsToMany(Bank::class, PersonelBank::class, 'personel_id', 'bank_id')->withPivot('id', 'branch', 'owner', 'rek_number', 'swift_code')->whereNull("bank_personels.deleted_at");
    }

    public function changePersonel()
    {
        return $this->hasOne(MarketingStatusChangeLog::class, 'personel_id', 'id')->latest();
    }

    public function personelHasBank()
    {
        return $this->hasMany(PersonelBank::class, 'personel_id', 'id');
    }

    public function personelBanks()
    {
        return $this->hasMany(PersonelBank::class, 'personel_id', 'id');
    }

    public function religion()
    {
        return $this->hasOne(Religion::class, 'id', 'religion_id')->withTrashed();
    }

    public function identityCard()
    {
        return $this->hasOne(IdentityCard::class, 'id', 'identity_card_type');
    }

    public function forecast()
    {
        return $this->hasMany(ForeCast::class, 'personel_id', "id");
    }

    public function areaMarketing()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id");
    }

    public function areaMarketingBelong()
    {
        return $this->belongsTo(MarketingAreaDistrict::class, "id", "personel_id");
    }

    public function areaAplicator()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "applicator_id", "id")->with("subRegionWithRegion");
    }

    public function areaAplicators()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "applicator_id", "id")->with("subRegionWithRegion");
    }

    public function scopeMarketingApplicatorUnderSupervisor($query, $personel_id = null)
    {
        $personel_ids = $this->getChildrenAplikatorV2($personel_id ? $personel_id : auth()->user()->personel_id);
        return $query->whereIn("personels.id", $personel_ids);
    }

    public function scopeMarketingMarketingUnderSupervisor($query, $personel_id = null)
    {
        $personel_ids = $this->getChildrenOneLevel($personel_id ? $personel_id : auth()->user()->personel_id);
        return $query->whereIn("personels.id", $personel_ids);
    }

    public function scopePermissionPickUp($query, $user){
        return [
            '(S) Pick Up Order',
        ];
    }

    public function area()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id")->with("subRegionWithRegion");
    }

    public function areaRegion()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id")
            ->with(["subRegionWithRegion" => function ($query) {
                return $query->with(["region" => function ($query) {
                    return $query->with("subRegion");
                }]);
            }]);
    }

    public function dealer()
    {
        return $this->hasOne(Dealer::class, "personel_id", "id");
    }

    public function dealerHasMany()
    {
        return $this->hasMany(DealerV2::class, "personel_id", "id");
    }

    public function storeHasMany()
    {
        return $this->hasMany(Store::class, "personel_id", "id");
    }

    public function nestedPersonel()
    {
        return $this->hasManyThrough(
            Personel::class,
            Personel::class,
            'supervisor_id', // Foreign key on the Sales_order table...
            'supervisor_id', // Foreign key on the invoice table...
            'id', // Local key on the Personel table...
            'id' // Local key on id sales_order table...
        );
    }

    public function achievementSettle()
    {
        return $this->hasManyThrough(
            Invoice::class,
            SalesOrder::class,
            'personel_id', // Foreign key on the Sales_order table...
            'sales_order_id', // Foreign key on the invoice table...
            'id', // Local key on the Personel table...
            'id' // Local key on id sales_order table...
        );
    }

    public function achievementAll()
    {
        return $this->hasManyThrough(
            Invoice::class,
            SalesOrder::class,
            'personel_id', // Foreign key on the Sales_order table...
            'sales_order_id', // Foreign key on the invoice table...
            'id', // Local key on the Personel table...
            'id' // Local key on id sales_order table...
        );
    }

    public function persentaseAchivement()
    {
        return $this->hasManyThrough(
            Invoice::class,
            SalesOrder::class,
            'personel_id', // Foreign key on the Sales_order table...
            'sales_order_id', // Foreign key on the invoice table...
            'id', // Local key on the Personel table...
            'id' // Local key on id sales_order table...
        );
    }

    public function scopeListBasePosition($QQQ, $position)
    {
        $position_marketing = [
            "Marketing Manager (MM)",
            "Marketing District Manager (MDM)",
            "Regional Marketing Coordinator (RMC)",
            "Regional Marketing (RM)",
        ];

        $position_list_name = [];

        if (in_array("marketing", $position)) {
            foreach ($position_marketing as $marketing) {
                array_push($position_list_name, $marketing);
            }
        }

        $position_list = DB::table('positions')
            ->whereNull("deleted_at")
            ->whereIn("name", $position_list_name)
            ->get()
            ->pluck("id");

        return $QQQ
            ->whereIn("position_id", $position_list);
    }

    public function marketingSalesActive()
    {
        return $this->hasMany(SalesOrder::class, "personel_id", "id");
    }
    public function marketingSalesActiveWithSettle()
    {
        return $this->hasMany(SalesOrder::class, "personel_id", "id");
    }
    public function district()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "personel_id", "id");
    }

    public function districtHasOne()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id");
    }
    public function subRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "id");
    }

    public function subRegionMarketingViaDistrict()
    {
        return $this->hasOneThrough(
            SubRegion::class,
            MarketingAreaDistrict::class,
            "personel_id",
            "id",
            "id",
            "sub_region_id",
        );
    }

    public function region()
    {
        return $this->hasOne(Region::class, "personel_id", "id");
    }
    public function user()
    {
        return $this->hasOne(User::class, "personel_id", "id")->withTrashed();
    }

    public function lastLoginDevice()
    {
        return $this->hasOneDeepFromRelations($this->user(), (new User())->lastLoginDevice())
            ->whereNotNull("user_access_histories.latitude")
            ->whereNotNull("user_access_histories.longitude");
    }

    public function lastLoginHistory($first_date = null, $second_date = null)
    {
        return $this->hasOneThrough(
            UserAccessHistory::class,
            User::class,
            "personel_id",
            "user_id",
            "id",
            "id"
        )
            ->whereNotNull("user_access_histories.latitude")
            ->whereNotNull("user_access_histories.longitude")
            ->when($first_date, function ($QQQ) use ($first_date) {
                return $QQQ->whereDate("user_access_histories.created_at", ">=", $first_date);
            })
            ->when($second_date, function ($QQQ) use ($second_date) {
                return $QQQ->whereDate("user_access_histories.created_at", "<=", $second_date);
            })
            ->orderBy("user_access_histories.created_at", "desc");
    }

    public function firstLoginHistoryToday()
    {
        return $this->hasOneThrough(
            UserAccessHistory::class,
            User::class,
            "personel_id",
            "user_id",
            "id",
            "id"
        )
            ->whereNotNull("user_access_histories.latitude")
            ->whereNotNull("user_access_histories.longitude")
            ->orderBy("user_access_histories.created_at", "asc")
            ->where("user_access_histories.created_at", ">=", now()->startOfDay());
    }

    public function loginHistories()
    {
        return $this->hasManyThrough(
            UserAccessHistory::class,
            User::class,
            "personel_id",
            "user_id",
            "id",
            "id"
        )
            ->whereNotNull("user_access_histories.latitude")
            ->whereNotNull("user_access_histories.longitude");
    }

    public function scopeLoginHistory($query, $first_date, $second_date)
    {
        return $query
            ->whereHas("loginHistories", function ($QQQ) use ($first_date, $second_date) {
                return $QQQ
                    ->whereNotNull("latitude")
                    ->whereNotNull("longitude")
                    ->whereDate("user_access_histories.created_at", ">=", $first_date)
                    ->whereDate("user_access_histories.created_at", "<=", $second_date);
            });
    }

    public function scopeSupervisor($query, $personel_id = null)
    {
        if (auth()->user()->hasAnyRole(
            "Marketing Manager (MM)",
        )) {
            return $query;
        } else {
            $personel_ids = $this->getChildren($personel_id ? $personel_id : auth()->user()->personel_id);
            return $query->whereIn("personels.id", $personel_ids);
        }
    }

    public function scopeSupervisorInverse($query, $area_level, $parent_area_id = null)
    {
        $personel_id = $this->marketingListForNewAreaBySupervising($area_level, $parent_area_id);

        /**
         * penfing code
         * $personel_id = $this->getChildren($area_level, $parent_area_id);
         */

        return $query->whereIn("personels.id", $personel_id);
    }

    public function scopeDriver($QQQ)
    {
        $position_list = DB::table('positions')
            ->whereNull("deleted_at")
            ->where("name", "supir")
            ->get()
            ->pluck("id");

        return $QQQ
            ->whereIn("position_id", $position_list);
    }

    /**
     * target base sub region if null
     *
     * @return void
     */
    public function getTargetAttribute()
    {
        $target = null;
        if (isset($this->attributes["target"])) {
            $target = $this->attributes["target"];
        }
        if (!$target) {
            $districts = $this->districtHasOne()->with("subRegionWithRegion")->first();
            $sub_region = $this->subRegion()->first();
            $region = $this->region()->first();
            if ($districts) {
                if ($districts->subRegionWithRegion) {
                    $target = $districts->subRegionWithRegion->target;
                }
            } else if ($sub_region) {
                $target = $sub_region->target;
            } else if ($region) {
                $target = $region->target;
            }
        }
        return $target;
    }

    // public function getPersentaseAchivementAttribute()
    // {

    //     $total = $this->forecast()->sum('nominal')/$this->achievement_all[0]->total_invoice;
    //     return $total;
    // }

    public function scopeListOnTarget($QQQ)
    {
        $personel_id = DB::table('marketing_area_districts')->whereNull("deleted_at")->whereNotNull("personel_id")->get()->pluck("personel_id");
        $personel_id = collect($personel_id)->unique();
        return $QQQ->whereIn("personels.id", $personel_id);
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, 'personel_id', 'id');
    }

    public function branch()
    {
        return $this->hasMnay(PersonelBranch::class, "personel_id", "id");
    }

    public function personelBranch()
    {
        return $this->hasMany(PersonelBranch::class, "personel_id", "id");
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personels.id", $marketing_on_branch);
    }

    public function point()
    {
        return $this->hasOne(PointMarketing::class, "personel_id", "id")
            ->orderBy("year", "desc");
    }

    public function currentPointMarketing()
    {
        return $this->hasOne(PointMarketing::class, "personel_id", "id")->where("year", Carbon::now()->format("Y"));
    }

    public function marketingFee()
    {
        return $this->hasMany(MarketingFee::class, "personel_id", "id");
    }

    public function currentMarketingFee()
    {
        return $this->hasMany(MarketingFee::class, "personel_id", "id")
            ->where("year", now()->format("Y"))
            ->orderBy("quarter");
    }

    public function currentQuarterMarketingFee()
    {
        return $this->hasOne(MarketingFee::class, "personel_id", "id")
            ->where("year", now()->format("Y"))
            ->where("quarter", now()->quarter)
            ->orderBy("quarter");
    }

    public function feeTargetSharingSoOrigins()
    {
        return $this->hasMany(FeeTargetSharingSoOrigin::class, "marketing_id", "id");
    }

    public function scopeFeeTargetSharingSoOrigin($query, $year, $quarter)
    {
        return $query
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter);
    }
    public function scopeTest($query, $year, $quarter)
    {
        return $query
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter);
    }

    public function scopePopular($query, $year, $quarter)
    {
        return $query->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter);
    }

    public function getIsApplicatorAttribute($query)
    {
        if (in_array($this->position->name, applicator_positions())) {
            return true;
        }

        return false;
    }

    public function areaMarketings()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "personel_id", "id");
    }

    public function getAllSupervisors()
    {
        $supervisors = collect();
        $personel = $this;

        while ($personel->supervisor !== null) {
            $supervisors->push($personel->supervisor);
            $personel = $personel->supervisor;
        }

        return $supervisors;
    }

    public function subordinates()
    {
        return $this->hasMany(Personel::class, 'supervisor_id');
    }


    public function photoLink(){
        return $this->photo ? Storage::disk("s3")->url("public/personel/". $this->photo): null;
    }
}
