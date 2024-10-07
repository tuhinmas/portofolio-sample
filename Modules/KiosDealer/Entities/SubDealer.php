<?php

namespace Modules\KiosDealer\Entities;

use Carbon\Carbon;
use App\Traits\Enums;
use App\Traits\Uuids;
use App\Traits\ChildrenList;
use App\Traits\FilterByArea;
use App\Traits\MarketingArea;
use App\Traits\CapitalizeText;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\Entity;
use Modules\DataAcuan\Entities\Region;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;
use Modules\LogPhone\Entities\LogPhone;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealer\Entities\Handover;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\KiosDealer\Entities\DealerFile;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\KiosDealer\Traits\ScopeSubDealer;
use Modules\Voucher\Entities\DiscountVoucher;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealerV2\Traits\ScopeDealerV2;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\Contest\Entities\ContestParticipant;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Analysis\Entities\DealerOrderRecapPerMonth;
use Modules\KiosDealer\Database\factories\SubDealerFactory;

class SubDealer extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use FilterByArea;
    use LogsActivity;
    use ChildrenList;
    use MarketingArea;
    use ScopeDealerV2;
    use SuperVisorCheckV2;
    use CapitalizeText;
    use ScopeSubDealer;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $guarded = [];
    protected $table = "sub_dealers";
    protected $casts = [
        "id" => "string",
    ];
    public $incrementing = false;
    protected $appends = [
        "prefix_sub_id",
        "prefix_id",
        "count_order",
        "indirect_sale_total_amount_order_based_quarter",
        "count_indirect_sale_total_amount_order_based_quarter",
        "last_order_indirect_sales",
        "day_last_order_indirect_sales",
        "store_point",
        "total_amount_life_time",
    ];

    public $enumStatuses = [
        'accepted', 'submission of changes', 'transfered',
    ];

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function getPrefixSubIdAttribute()
    {
        return config("app.sub_dealer_id_prefix");
    }

    public function getPrefixIdAttribute()
    {
        return config("app.sub_dealer_id_prefix");
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'like', $name);
    }

    public function scopeFilterAll($QQQ, $filter)
    {

        $is_mm = DB::table('personels as p')
            ->join("positions as po", "p.position_id", "po.id")
            ->whereIn("po.name", position_mm())
            ->where("p.id", auth()->user()->personel_id)
            ->where("p.status", "1")
            ->first();

        // $sub_dealer_or_sub_dealer_be_dealer = [];
        ;

        return $QQQ
            ->with('haveContestRunning')
            // ->when($is_mm, function ($QQQ) {
            //     return $QQQ->whereNull("personel_id");
            // })
            ->where(function ($QQQ) use ($filter) {
                return $QQQ
                    ->where(function ($query) use ($filter) {
                        $query->where("name", "like", "%" . $filter . "%")
                            ->orWhere("owner", "like", "%" . $filter . "%");
                    })
                    ->orWhere("sub_dealer_id", "like", "%" . $filter . "%")
                    ->orWhereHas('personel', function ($query) use ($filter) {
                        $query->where('name', 'like', '%' . $filter . '%');
                    });
            });
    }

    protected static function newFactory()
    {
        return SubDealerFactory::new ();
    }

    public function subDealerFile()
    {
        return $this->hasMany(DealerFile::class, 'dealer_id', 'id');
    }

    public function adressDetail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function addressDetail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id')->with("position");
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'id', 'handover_status');
    }

    public function salesOrderDealer()
    {
        return $this->hasMany(SalesOrder::class, 'store_id', 'id')
            ->where("status", "confirmed")
            ->where("model", "2")
            ->where("type", "2")
            ->with("sales_order_detail");
    }

    public function salesOrderDealerSubDealer()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id');
    }

    public function statusFee()
    {
        return $this->hasOne(StatusFee::class, 'id', 'status_fee');
    }

    /**
     * count total order
     *
     * @return void
     */
    public function getCountOrderAttribute()
    {
        return $this->salesOrderDealer()
            ->whereYear("date", Carbon::now())
            ->count();
    }

    /**
     * count total order
     *
     * @return void
     */
    public function getTotalAmountLifeTimeAttribute()
    {
        return $this->salesOrderDealer()
            ->whereYear("date", Carbon::now())
            ->sum("total");
    }

    public function indirectSalesTotalAmountBasedQuarter()
    {
        $quarter_first = Carbon::now()->startOfQuarter()->month;
        $first_quarter_indirect = Carbon::now()->subMonths($quarter_first - 1)->startOfQuarter();
        return $this->salesOrderDealer()
            ->where("sales_orders.date", ">=", $first_quarter_indirect)
            ->select("sales_orders.*");
    }

    /**
     * direct sales total amount base on quarter
     *
     * @return void
     */
    public function getIndirectSaleTotalAmountOrderBasedQuarterAttribute()
    {
        $sales_orders = $this->indirectSalesTotalAmountBasedQuarter()->get();
        $total_amount = $sales_orders->sum("total");
        return $total_amount;
    }

    /**
     * direct sales total amount base on quarter
     *
     * @return void
     */
    public function getCountIndirectSaleTotalAmountOrderBasedQuarterAttribute()
    {
        $count = $this->indirectSalesTotalAmountBasedQuarter()->count();
        return $count;
    }

    /** last order */
    public function getLastOrderIndirectSalesAttribute()
    {
        $sales_order = $this->salesOrderDealer()
            ->whereYear("date", Carbon::now())
            ->where("type", "2")
            ->orderBy("date", "desc")
            ->first();

        $last_order = null;

        if ($sales_order) {
            $last_order = $sales_order->date;
            return Carbon::createFromFormat('Y-m-d H:i:s', $last_order, 'UTC')->setTimezone('Asia/Jakarta');
        }

        return null;
    }

    /** day last order */
    public function getDayLastOrderIndirectSalesAttribute()
    {
        $days = 0;
        $last_order = $this->getLastOrderIndirectSalesAttribute();
        if ($last_order) {
            $days = $last_order->diffInDays(Carbon::now());
        }
        return $days;
    }

    public function getStorePointAttribute()
    {
        return "0";
    }

    public function scopeSupervisor($query, $personel_id = null)
    {

        $personel_id = $this->getPersonel($personel_id);
        if (auth()->user()->hasAnyRole(
            'administrator',
            'super-admin',
            'marketing staff',
            'Marketing Support',
            'Marketing Manager (MM)',
            'Sales Counter (SC)',
            'Operational Manager',
            'Support Bagian Distributor',
            'Support Distributor',
            'Support Bagian Kegiatan',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
        )) {
            return $query;
        } else {
            return $query->whereIn("personel_id", $personel_id);
        }
    }

    /**
     * filter by region
     *
     * @param [type] $QQQ
     * @return void
     */
    public function scopeDistributorByArea($QQQ)
    {
        $dealer_on_area = $this->scopeByArea();
        return $QQQ->whereIn("id", $dealer_on_area);
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
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "sub_dealer")
            ->whereIn("district_id", $district_list_on_region)
            ->get()
            ->pluck("parent_id")
            ->toArray();
        return $QQQ->whereIn("id", $dealer_address);
    }

    /**
     * filter dealer by sub_region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */

    public function scopeSubRegion($QQQ, $sub_region_id)
    {
        $district_list_on_subregion = $this->districtListByAreaId($sub_region_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "sub_dealer")
            ->whereIn("district_id", $district_list_on_subregion)
            ->get()
            ->pluck("parent_id")
            ->toArray();

        return $QQQ->whereIn("id", $dealer_address);
    }

    public function scopeSubRegionArray($QQQ, $sub_region_id)
    {
        $district_list_on_subregion = $this->districtListByAreaIdArray($sub_region_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "sub_dealer")
            ->whereIn("district_id", $district_list_on_subregion)
            ->get()
            ->pluck("parent_id")
            ->toArray();

        return $QQQ->whereIn("id", $dealer_address);
    }

    public function scopeSubRegionName($QQQ, $sub_region)
    {
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "sub_dealer")
            ->where("name", "like", "%" . $sub_region . "%")
            ->get()
            ->pluck("parent_id")
            ->toArray();

        return $QQQ->whereIn("id", $dealer_address);
    }

    public function scopeDistrict($QQQ, $district_id)
    {
        $district_list_on_district = $this->districtListById($district_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "sub_dealer")
            ->whereIn("district_id", $district_list_on_district)
            ->get()
            ->pluck("parent_id")
            ->toArray();

        return $QQQ->whereIn("id", $dealer_address);
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, "store_id", "sales_order_id")->with("sales_order_detail", "invoice");
    }

    public function scopeApplicator($query, $applicator_id)
    {
        $MarketingAreaDistrict = MarketingAreaDistrict::when($applicator_id, function ($query) use ($applicator_id) {
            return $query->where("applicator_id", $applicator_id);
        })->get()->map(function ($data) {
            return $data->id;
        });
        // dd($MarketingAreaDistrict);

        return $query->whereHas("areaDistrictStore", function ($Q) use ($MarketingAreaDistrict) {
            return $Q->whereIn("marketing_area_districts.id", $MarketingAreaDistrict);
        });
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, "store_id", "sales_order_id")->with("sales_order_detail", "invoice");
    }

    public function salesOrderSubDelaer()
    {
        return $this->hasMany(SalesOrder::class, "store_id", "id")->orderBy("date");
    }

    public function lastOrderSubDealer()
    {
        return $this->hasOne(SalesOrder::class, "store_id", "id")
            ->orderBy("date", "desc")
            ->where("status", "confirmed")
            ->where("model", "2")
            ->where("type", "2");
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

    public function subRegionSubDealer()
    {
        $sub_region = $this->subRegionHasOne()->where("address_with_details.type", "sub_dealer")->with("subRegion");
        return $sub_region;
    }

    public function regionSubDealer()
    {
        $region = $this->regionHasOne()->where("address_with_details.type", "sub_dealer")->with("subRegion", "subRegion.region.personel");
        return $region;
    }

    public function subDealerAddress()
    {
        return $this->adressDetail()->where("type", "sub_dealer")->with('marketingAreaDistrict');
    }

    public function scopeAcceptedOnly($query)
    {
        return $query->where("status", "accepted");
    }

    public function scopeByDateBetween($query, $start_date, $end_date)
    {
        return $query->whereDate('created_at', '>=', $start_date)
            ->whereDate('created_at', '<=', $end_date);
    }

    public function areaDistrictSubDealer()
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
        )
            ->with('subRegion')
            ->where("address_with_details.type", "sub_dealer");
    }

    public function areaDistrictStore()
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
        )
            ->with('subRegion', "province", "city", "district")
            ->where("address_with_details.type", "sub_dealer");
    }

    public function ditributorContract()
    {
        return $this->hasMany(DistributorContract::class, "dealer_id", "id");
    }

    public function distributorContract()
    {
        return $this->hasMany(DistributorContract::class, "dealer_id", "id");
    }

    public function distributorContractActive()
    {
        return $this->hasOne(DistributorContract::class, "dealer_id", "id")
            ->whereDate("contract_start", "<=", now())
            ->whereDate("contract_end", ">=", now());
    }

    public function subRegionSubDealerDeepRelation()
    {
        return $this->hasOneDeepFromRelations($this->areaDistrictSubDealer(), (new MarketingAreaDistrict())->subRegionWithRegion());
    }

    public function scopeByNameOrOwnerOrSubDealerId($QQQ, $filter)
    {
        return $QQQ
            ->where("name", "like", "%" . $filter . "%")
            ->orWhere("sub_dealer_id", "like", "%" . $filter . "%")
            ->orWhere("owner", "like", "%" . $filter . "%");
    }

    public function areaDistrictDealer()
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
        )
            ->with('subRegion', "province", "city", "district")
            ->where("address_with_details.type", "sub_dealer");
    }

    public function subDealerTemp()
    {
        return $this->hasOne(SubDealerTemp::class, "sub_dealer_id", "id");
    }

    public function dealerTemp()
    {
        return $this
            ->hasOne(DealerTemp::class, "sub_dealer_id", "id")
            ->whereNotIn("status", ["filed rejected", "change rejected"]);
    }

    public function scopeHasDealerTemp($query)
    {
        return $query->whereHas("dealerTemp");
    }

    public function haveContestRunning()
    {
        return $this->hasOne(ContestParticipant::class, "sub_dealer_id", "id")
            ->with('contest')
            ->where("participant_status", "4")
            ->whereHas('contest', function ($q) {
                $q->where('period_date_start', '<=', date('Y-m-d'))->where('period_date_end', '>=', date('Y-m-d'));
            });
    }

    public function contestParticiapant()
    {
        return $this->hasMany(ContestParticipant::class, "sub_dealer_id", "id");
    }

    public function dealer()
    {
        return $this->morphMany(DealerOrderRecapPerMonth::class, 'dealer');
    }

    public function recapOrder()
    {
        return $this->hasmany(DealerOrderRecapPerMonth::class, "dealer_id", "id")
            ->where("dealer_type", "sub_dealer");
    }

    public function agencyLevel()
    {
        return $this->hasOne(AgencyLevel::class, 'id', 'agency_level_id');
    }

    public function area()
    {
        return $this->hasOne(Address::class, "parent_id", "id")
            ->where("type", "dealer");
    }

    public function addressSubDealer()
    {
        return $this->hasOne(Address::class, "parent_id", "id")->where("type", "sub_dealer");
    }

    public function stores()
    {
        return $this->hasMany(DiscountVoucher::class, 'store_id');
    }

    public function activeContractContest()
    {
        return $this->hasOne(ContestParticipant::class, "sub_dealer_id", "id")->activeContractStoreByDate($this->id, now());
    }

    public function logPhones()
    {
        return $this->hasMany(LogPhone::class, "model_id", "id")->where('type', 'phone');
    }
}
