<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\CapitalizeText;
use App\Traits\ChildrenList;
use App\Traits\FilterByArea;
use App\Traits\MarketingArea;
use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\Analysis\Entities\DealerOrderRecapPerMonth;
use Modules\Contest\Entities\ContestParticipant;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Bank;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\DealerAgencyLevelChangeLog\Entities\DealerAgencyLevelChangeLog;
use Modules\Distributor\Entities\DistributorArea;
use Modules\Distributor\Entities\DistributorContract;
use Modules\ForeCast\Entities\ForeCast;
use Modules\KiosDealerV2\Traits\ScopeDealerV2;
use Modules\KiosDealer\Entities\DealerDeliveryAddress;
use Modules\KiosDealer\Entities\DealerFile;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\Handover;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Organisation\Entities\Entity;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\Traits\CustomPersonnelLogic;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Voucher\Entities\DiscountVoucher;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Dealer extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use FilterByArea;
    use ChildrenList;
    use LogsActivity;
    use ScopeDealerV2;
    use MarketingArea;
    use SuperVisorCheckV2;
    use CascadeSoftDeletes;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use CapitalizeText;
    use CustomPersonnelLogic;

    public $incrementing = false;
    protected $cascadeDeletes = ['adress_detail'];

    protected $casts = [
        "id" => "string",
    ];

    public static $withoutAppends = false;

    protected $guarded = [];
    protected $appends = [
        "prefix_id",
        // "amount_order", //
        // "count_order", //
        // "last_order", //
        // "paid_amount", //
        // "unpaid_amount", //
        // "days_last_order", //
        // "last_order_global",
        // "direct_sale_last_order", //
        // "sales_counter",
        "grading",
        "dealer_has_payment",
        // "store_point",
        // "direct_sale_total_amount_order_based_quarter", //
        // "count_direct_sale_order_based_quarter", //
        // "direct_sale_paid_amount_based_quarter", //
        // "direct_sale_unpaid_amount_based_quarter", //
        // "indirect_sale_total_amount_order_based_quarter", //
        // "count_indirect_sale_order_based_quarter", //
        // "last_order_indirect_sales", //
        // "active_status",
        'custom_credit_limit',
        // "active_status_one_year",
    ];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerFactory::new ();
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

    public function dealer_file()
    {
        return $this->hasMany(DealerFile::class, 'dealer_id', 'id');
    }

    public function dealerFile()
    {
        return $this->hasMany(DealerFile::class, 'dealer_id', 'id');
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id')->with("benefit");
    }

    public function suggestedGrading()
    {
        return $this->hasOne(Grading::class, 'id', 'suggested_grading_id');
    }

    public function dealerGrading()
    {
        return $this->hasMany(DealerGrading::class, "dealer_id", "id")->latest();
    }

    public function dealerGradingBefore()
    {
        return $this->hasOne(DealerGrading::class, "dealer_id", "id")->latest()->skip(1)->take(1);
    }

    public function lastGrading()
    {
        return $this->hasOne(DealerGrading::class, "dealer_id", "id")->latest();
    }

    public function getGradingAttribute($value)
    {
        $dealer_grading = $this->dealerGrading()->first();
        $grading = $this->grading()->first();
        if ($grading) {
            if ($dealer_grading) {
                if ($dealer_grading->custom_credit_limit) {
                    $grading->credit_limit = $dealer_grading->custom_credit_limit;
                }
            }
        }
        return $grading;
    }

    public function attachGrading()
    {
        return $this->belongsToMany(Grading::class, "dealer_gradings", "dealer_id", "grading_id")
            ->withTimeStamps()
            ->withPivot("custom_credit_limit", "user_id")
            ->using(new class extends Pivot
        {
                use Uuids;
                protected $casts = ["id" => "string"];
            });
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, 'id', 'personel_id');
    }

    public function agencyLevel()
    {
        return $this->hasOne(AgencyLevel::class, 'id', 'agency_level_id');
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    public function dealer_file_confirmation()
    {
        return $this->hasMany(DealerFile::class, 'dealer_id', 'id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'id', 'handover_status');
    }

    public function statusFee()
    {
        return $this->hasOne(StatusFee::class, 'id', 'status_fee');
    }

    public function adress_detail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function addressDetail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function adressDetail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function area()
    {
        return $this->hasOne(Address::class, "parent_id", "id")
            ->where("type", "dealer");
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, 'store_id', 'id');
    }

    public function salesOrderAsDistributor()
    {
        return $this->hasMany(SalesOrder::class, 'distributor_id', 'id');
    }

    public function salesOrderConfirmed()
    {
        return $this->hasMany(SalesOrder::class, 'store_id', 'id')
            ->where("status", "confirmed")
            ->where("model", "1")
            ->where("type", "1");
    }

    public function salesOrderIndirectConfirmed()
    {
        return $this->hasMany(SalesOrder::class, 'store_id', 'id')
            ->where("status", "confirmed")
            ->where("model", "1")
            ->where("type", "2");
    }

    public function salesOrderDealer()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id')
            ->where("status", "confirmed")
            ->where("model", "1");
    }

    public function salesOrderDealerSubDealer()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id');
    }

    public function salesOrderOnly()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id')
            ->where("model", "1");
    }

    public function consideredSalesOrder()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id')
            ->where("model", "1")
            ->consideredOrder();
    }

    public function salesOrderOnlyCek()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id')
            ->where("model", "1")
            ->where('status', 'confirmed')->latest();
    }

    public function salesOrderInactive()
    {
        return $this->hasMany(SalesOrderV2::class, 'store_id', 'id')
            ->where("model", "1");
    }

    public function dealerPayment()
    {
        return $this->belongsToMany(PaymentMethod::class, "dealer_payment_methods", "dealer_id", "payment_method_id")
            ->withTimeStamps()
            ->using(new class extends Pivot
        {
                use Uuids;
                protected $casts = ["id" => "string"];
            });
    }

    public function dealerWithPayment()
    {
        return $this->hasMany(DealerPaymentMethod::class, "dealer_id", "id")->with("paymentMethod");
    }

    public function getDealerHasPaymentAttribute()
    {
        $payments = $this->dealerWithPayment()->get();
        $grading = $this->grading()->first();
        $grading_name = "Hitam";
        if ($grading) {
            $grading_name = $grading->name;
        }
        if (count($payments) != 0) {
            return $this->allPayment()
                ->filter(function ($payment) use ($payments) {
                    return in_array($payment->id, collect($payments)->pluck("payment_method_id")->toArray());
                })
                ->values();
        } else if (count($payments) == 0 && $grading_name == "Hitam") {
            return $this->allPayment()
                ->filter(function ($payment) use ($payments) {
                    return in_array($payment->id, collect($payments)->pluck("payment_method_id")->toArray());
                })
                ->values();
            return $payments;
        } else {
            return $this->allPayment();
        }
    }

    public function getPrefixIdAttribute()
    {
        return config("app.dealer_id_prefix");
    }

    /**
     * sum total invoice
     *
     * @param [type] $query
     * @return void
     */
    public function getAmountOrderAttribute()
    {
        // return 0;

        $salesOrders = $this->salesOrderConfirmed()
            ->with("invoice")
            ->whereHas("invoice", function ($QQQ) {
                return $QQQ
                    ->with([
                        "payment",
                    ])
                    ->whereYear("created_at", Carbon::now())
                    ->orderBy("created_at", "desc");
            })
            ->get();
        $total_amount = $salesOrders->where("type", "1")->sum("invoice.total");
        $total_ppn = $salesOrders->where("type", "1")->sum("invoice.ppn");
        $total_amount_indirect = $this->salesOrderDealer()->where("type", "2")->whereYear("date", Carbon::now())->sum("total");
        return $total_amount + $total_ppn + $total_amount_indirect;
    }

    /**
     * count total order
     *
     * @return void
     */
    public function getCountOrderAttribute()
    {
        // return 0;
        return $this->salesOrderDealer()->count();
    }

    public function getLastOrderAttribute()
    {
        // return null;
        $sales_orders = $this->salesOrderDealer()
            ->with("invoice")
            ->where(function ($Q) {
                return $Q
                    ->where("type", "1")
                    ->whereHas("invoice");
            })
            ->get();

        $direct_sale = $sales_orders->where("type", "1")->sortByDesc("invoice.created_at")->first();
        $indirect_sale = $sales_orders->where("type", "2")->sortByDesc("date")->first();

        $last_direct = null;
        $last_indirect = null;
        $last_order = null;

        if ($direct_sale) {
            $last_direct = $direct_sale->invoice->created_at;
            $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $direct_sale->invoice->created_at, 'UTC')->setTimezone('Asia/Jakarta');
        }

        if ($indirect_sale) {
            $last_indirect = Carbon::createFromFormat('Y-m-d H:i:s', $indirect_sale->date, 'UTC')->setTimezone('Asia/Jakarta');
            $last_order = $last_indirect;
        }

        if ($last_direct > $last_indirect) {
            $last_order = $last_direct;
        }

        return $last_order;
    }

    /**
     * last order direct or indirect
     *
     * @return void
     */
    public function getDaysLastOrderAttribute()
    {
        $last_order = $this->getLastOrderAttribute();
        if ($last_order) {
            return Carbon::createFromFormat('Y-m-d', $last_order->format("Y-m-d"))->diffInDays(Carbon::createFromFormat('Y-m-d', Carbon::now()->format("Y-m-d")), false);
            // return $last_order->diffInDays(Carbon::now());
        } else {
            return 0;
        }
    }

    public function getLastOrderGlobalAttribute()
    {
        $sales_orders = $this->salesOrderDealer()
            ->with("invoice")
            ->whereIn("status", ["submited", "confirmed"])
            ->where(function ($Q) {
                return $Q
                    ->where("type", "1")
                    ->whereHas("invoice");
            })
            ->get();

        $direct_sale = $sales_orders->where("type", "1")->sortByDesc("invoice.created_at")->first();
        $indirect_sale = $sales_orders->where("type", "2")->sortByDesc("date")->first();

        $last_direct = null;
        $last_indirect = null;
        $last_order = null;

        if ($direct_sale) {
            $last_direct = $direct_sale->invoice->created_at;
            $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $direct_sale->invoice->created_at, 'UTC')->setTimezone('Asia/Jakarta');
        }

        if ($indirect_sale) {
            $last_indirect = Carbon::createFromFormat('Y-m-d H:i:s', $indirect_sale->date, 'UTC')->setTimezone('Asia/Jakarta');
            $last_order = $last_indirect;
        }

        if ($last_direct > $last_indirect) {
            $last_order = $last_direct;
        }

        return $last_order;
    }

    /**
     * active status from submited
     *
     * @return void
     */
    public function getActiveStatusAttribute()
    {
        $last_order = $this->getLastOrderGlobalAttribute();
        $days = 0;

        if ($last_order) {
            $days = $last_order->diffInDays(Carbon::now());
            if ($days == 0) {
                $days = 1;
            }
        } else {
            $created_day = null;

            $created_day = Carbon::create($this->created_at);

            $days = $created_day->diffInDays(Carbon::now());
            if ($days == 0) {
                $days = 1;
            }
        }

        $follow_up_days = DB::table("fee_follow_ups")
            ->orderBy("follow_up_days")
            ->first();

        $follow_up_days_base_account = $follow_up_days->follow_up_days;

        if (!auth()->user()->hasAnyRole("support", "super-admin", "Marketing Support", "Operational Manager", "Sales Counter (SC)", 'Operational Manager', 'Distribution Channel (DC)')) {
            $follow_up_days_base_account -= 15;
        }

        if ($days > 0) {
            if ($days > $follow_up_days_base_account) {
                return false;
            } else {
                if ($this->deleted_at !== null) {
                    return false;
                }
                return true;
            }
        } else {
            return false;
        }
    }

    public function getDirectSaleLastOrderAttribute()
    {

        $sales_orders = $this->salesOrderConfirmed()
            ->with("invoice")
            ->whereHas("invoice", function ($QQQ) {
                return $QQQ->orderBy("created_at", "desc");
            })
            ->first();
        // $sales_order = $sales_orders->sortByDesc("invoice.created_at")->first();
        $sales_order = $sales_orders;
        $last_order = null;
        if ($sales_order) {
            $last_order = $sales_order->invoice->created_at;
            $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $last_order, 'UTC')->setTimezone('Asia/Jakarta');
        }
        return $last_order;
    }

    /**
     * total paid
     */
    public function getPaidAmountAttribute()
    {
        $sales_orders = $this->salesOrderConfirmed()->with("invoice")->get();
        $amount = 0;
        foreach ($sales_orders as $sales_order) {
            if ($sales_order->invoice) {
                $amount += collect($sales_order->invoice->payment)->sum("nominal");
            }
        }
        return $amount;
    }

    // public function getUnpaidAmountAttribute()
    // {
    //     return $this->getAmountOrderAttribute() - $this->getPaidAmountAttribute();
    // }

    public function getSalesCounterAttribute()
    {
        $sales_orders = $this->salesOrderConfirmed()->get();
        $sales_order = $sales_orders->sortByDesc("created_at")->first();
        $sales_counter = null;
        if ($sales_order) {
            if ($sales_order->salesCounter) {
                $sales_counter = $sales_order->salesCounter;
            }
        }

        return $sales_counter;
    }

    public function allPayment()
    {
        try {
            $payments = PaymentMethod::orderBy("name")->get();
            return $payments;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function directSalesTotalAmountBasedQuarter()
    {
        $quarter_first = Carbon::now()->subQuarter(3)->startOfQuarter();
        return $this->salesOrderConfirmed()
            ->with([
                "invoice" => function ($Q) {
                    return $Q->with("payment");
                },
            ])
            ->whereHas("invoice", function ($QQQ) use ($quarter_first) {
                return $QQQ->where("created_at", ">=", $quarter_first);
            });
    }

    /**
     * direct sales total amount base on quarter
     *
     * @return void
     */
    public function getDirectSaleTotalAmountOrderBasedQuarterAttribute()
    {
        // return 0;
        $sales_orders = $this->directSalesTotalAmountBasedQuarter()->get();
        $total_amount = $sales_orders->sum("invoice.total");
        $ppn = $sales_orders->sum("invoice.ppn");
        return $total_amount + $ppn;
    }

    /**
     * direct sales total amount base on quarter
     *
     * @return void
     */
    public function getCountDirectSaleOrderBasedQuarterAttribute()
    {
        // return 0;
        $count = $this->directSalesTotalAmountBasedQuarter()->count();
        return $count;
    }

    /**
     * direct sale amount paid
     *
     * @return void
     */
    public function getDirectSalePaidAmountBasedQuarterAttribute()
    {
        // return 0;
        $sales_orders = $this->directSalesTotalAmountBasedQuarter()->get();
        $amount = 0;
        foreach ($sales_orders as $sales_order) {
            if ($sales_order->invoice) {
                $amount += collect($sales_order->invoice->payment)->sum("nominal");
            }
        }
        return $amount;
    }

    /**
     * direct sale unpaid amount
     *
     * @return void
     */
    // public function getDirectSaleUnpaidAmountBasedQuarterAttribute()
    // {
    //     return $this->getDirectSaleTotalAmountOrderBasedQuarterAttribute() - $this->getDirectSalePaidAmountBasedQuarterAttribute();
    // }

    public function getStorePointAttribute()
    {
        return "0";
    }

    /**
     * indirect sale dealer list
     *
     * @param [type] $query
     * @return void
     */
    public function indirectSalesTotalAmountBasedQuarter()
    {
        $quarter_first = Carbon::now()->subQuarter(3)->startOfQuarter();
        return $this->salesOrderDealer()
            ->where("sales_orders.date", ">=", $quarter_first)
            ->where("type", "2")
            ->select("sales_orders.*");
    }

    /**
     * direct sales total amount base on quarter
     *
     * @return void
     */
    // public function getIndirectSaleTotalAmountOrderBasedQuarterAttribute()
    // {
    //     // return 0;
    //     $sales_orders = $this->indirectSalesTotalAmountBasedQuarter()->get();
    //     $total_amount = $sales_orders->sum("total");
    //     return $total_amount;
    // }

    /**
     * direct sales total amount base on quarter
     *
     * @return void
     */
    // public function getCountIndirectSaleOrderBasedQuarterAttribute()
    // {
    //     $count = $this->indirectSalesTotalAmountBasedQuarter()->count();
    //     return $count;
    // }

    // public function getLastOrderIndirectSalesAttribute()
    // {
    //     // return Carbon::now();
    //     // $sales_order = $this->salesOrderDealer()->orderBy("created_at", "desc")->first();
    //     $sales_order = $this->indirectSalesTotalAmountBasedQuarter()->orderBy("sales_orders.date")->first();
    //     $last_order = null;
    //     if ($sales_order) {
    //         $last_order = $sales_order->date;
    //         $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $last_order, 'UTC')->setTimezone('Asia/Jakarta');
    //     }
    //     return $last_order;
    // }

    public function distributorContract()
    {
        return $this->hasOne(DistributorContract::class, "dealer_id", "id");
    }

    public function distributorContractActive()
    {
        return $this->hasOne(DistributorContract::class, "dealer_id", "id")
            ->whereDate("contract_start", "<=", now())
            ->whereDate("contract_end", ">=", now());
    }

    public function ditributorContract()
    {
        return $this->hasMany(DistributorContract::class, "dealer_id", "id");
    }

    public function contractDistributor()
    {
        return $this->hasMany(DistributorContract::class, "dealer_id", "id");
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
        )->where("address_with_details.type", "dealer");
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
            ->where("address_with_details.type", "dealer");
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
            ->withTrashed()
            ->with('subRegion', "province", "city", "district")
            ->where("address_with_details.type", "dealer");
    }

    public function subRegionDealerDeepRelation()
    {
        return $this->hasOneDeepFromRelations($this->areaDistrictDealer(), (new MarketingAreaDistrict())->subRegionWithRegion());
    }

    public function regionDealerDeepRelation()
    {
        return $this->hasOneDeepFromRelations($this->subRegionDealerDeepRelation(), (new SubRegion())->region());
    }

    public function subRegionDealer()
    {
        $sub_region = $this->subRegionHasOne()->where("address_with_details.type", "dealer");
        return $sub_region;
    }

    public function scopeSubRegionDealerFilter($region_id)
    {
        $district_list_on_region = $this->districtListByAreaId($region_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "dealer")
            ->whereIn("district_id", $district_list_on_region)
            ->get()
            ->pluck("parent_id")
            ->toArray();
        $sub_region = $this->areaDistrictDealer()->whereIn("parent_id", $dealer_address);
        return $sub_region;
    }

    public function regionDealer()
    {
        $region = $this->regionHasOne()->where("address_with_details.type", "dealer")->with("subRegion", "subRegion.region.personel");
        return $region;
    }

    public function totalSales4QuartalSettle()
    {
        return $this->salesOrderDealer();
    }

    public function scopeSupervisor($query)
    {
        $personel_id = $this->getPersonel();
        $user_position = DB::table('positions')->whereNull("deleted_at")->where("name", auth()->user()->profile->position->name)->first();
        $positions = [
            "Marketing Manager (MM)",
            "Marketing Support",
            "Operational Manager",
            "Distribution Channel (DC)",
        ];

        if (in_array($user_position->name, $positions)) {
            return $query->whereNotNull("personel_id");
        } else {
            return $query->whereIn("personel_id", $personel_id)->whereNotNull("personel_id");
        }
    }

    public function scopeName($QQQ, $name)
    {
        return $QQQ->where("name", "like", "%" . $name . "%");
    }

    public function scopeFilterAll($QQQ, $filter)
    {
        return $QQQ->where("name", "like", "%" . $filter . "%")
            ->orWhere("dealer_id", "like", "%" . $filter . "%")
            ->orWhere("owner", "like", "%" . $filter . "%")
            ->orWhereHas('personel', function ($query) use ($filter) {
                $query->where('name', 'LIKE', '%' . $filter . '%');
            });
    }

    /**
     * filter distributor by area marketing
     *
     * @param [type] $QQQ
     * @return void
     */
    public function scopeDistributorByArea($QQQ, $district_id)
    {
        $dealer_on_area = $this->scopeByArea($district_id);
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
            ->where("type", "dealer")
            ->whereIn("district_id", $district_list_on_region)
            ->get()
            ->pluck("parent_id")
            ->toArray();
        return $QQQ->whereIn("id", $dealer_address);
    }

    // public function scopeRegion2($QQQ, $region_id)
    // {
    //     $regions = Region::query()
    //         ->where("id", $region_id)
    //         ->with("subRegion")
    //         ->get();

    //     // return $regions;
    //     $cek = [];
    //     foreach($regions as $key => $value){
    //         $cek[] = $value->subRegion;
    //     }
    //     dd($cek);
    // }

    /**
     * filter dealer by region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */
    public function scopeSubRegion($QQQ, $sub_region_id)
    {
        $district_list_on_region = $this->districtListByAreaId($sub_region_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "dealer")
            ->whereIn("district_id", $district_list_on_region)
            ->get()
            ->pluck("parent_id")
            ->toArray();
        return $QQQ->whereIn("id", $dealer_address);
    }

    /**
     * filter dealer by region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */
    public function scopeSubRegionArray($QQQ, $sub_region_id)
    {
        $district_list_on_region = $this->districtListByAreaIdArray($sub_region_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "dealer")
            ->whereIn("district_id", $district_list_on_region)
            ->get()
            ->pluck("parent_id")
            ->toArray();
        // dd($dealer_address);
        return $QQQ->whereIn("id", $dealer_address);
    }

    public function scopeSubRegionName($QQQ, $sub_region)
    {
        $sub_region_id = DB::table('marketing_area_sub_regions')
            ->select("id", "name")
            ->whereNull("deleted_at")
            ->where("name", "like", "%" . $sub_region . "%")
            ->get()
            ->pluck("id")
            ->toArray();

        $district = DB::table('marketing_area_districts')
            ->select("id", "district_id")
            ->whereNull("deleted_at")
            ->whereIn("sub_region_id", $sub_region_id)
            ->get()
            ->pluck("district_id")
            ->toArray();

        $dealer_address = DB::table('address_with_details')
            ->select("id", "district_id")
            ->whereNull("deleted_at")
            ->whereIn("district_id", $district)
            ->get()
            ->pluck("parent_id")
            ->toArray();

        return $QQQ->whereIn("id", $dealer_address);
    }

    /**
     * filter dealer by district
     *
     * @param [type] $QQQ
     * @param [type] $district_id
     * @return void
     */
    public function scopeDistributorByAreaDistrict($QQQ, $district_id)
    {
        $district_list = $this->districtListByDistrictId($district_id);
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("type", "dealer")
            ->whereIn("district_id", $district_list)
            ->get()
            ->pluck("parent_id")
            ->toArray();
        return $QQQ->whereIn("id", $dealer_address);
    }

    public function subDealer()
    {
        return $this->hasOne(SubDealer::class, "dealer_id", "id");
    }

    public function agencyLog()
    {
        return $this->belongsToMany(AgencyLevel::class, DealerAgencyLevelChangeLog::class, 'dealer_id', 'agency_level_id')
            ->using(new class extends Pivot
        {
                use Uuids;
                protected $casts = ["id" => "string"];
            })->withTimeStamps();
    }

    public function changeAgencyLog()
    {
        return $this->hasOne(DealerAgencyLevelChangeLog::class, 'dealer_id', 'id')->latest();
    }

    /**
     * distributor list by area marketing personel
     *
     * @param [type] $QQQ
     * @param [type] $personel_id
     * @return void
     */
    public function scopeDistributorByPersonelArea($QQQ, $personel_id)
    {
        $district_id = $this->districtListMarketing($personel_id);
        $contract_id = DistributorArea::query()
            ->whereIn("district_id", $district_id)
            ->whereHas("contract", function ($QQQ) {
                return $QQQ->whereHas("dealer");
            })
            ->get()
            ->pluck("contract_id");

        $distributor_id = DistributorContract::query()
            ->whereIn("id", $contract_id)
            ->get()
            ->pluck("dealer_id");

        return $QQQ->whereIn("id", $distributor_id)->where("is_distributor", "1");
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

    public function foreCast()
    {
        return $this->hasMany(ForeCast::class, 'dealer_id', 'id');
    }

    public function getActiveStatusOneYearAttribute()
    {
        if ($this->getLastOrderAttribute() != null) {
            $check = Carbon::now()->between($this->getLastOrderAttribute(), Carbon::createFromFormat('Y-m-d H:i:s', $this->getLastOrderAttribute())->addYear());
            return $check;
        } else {
            return "-";
        }
    }

    public function dealerBank()
    {
        return $this->hasOne(Bank::class, "id", "bank_id");
    }

    public function ownerBank()
    {
        return $this->hasOne(Bank::class, "id", "owner_bank_id");
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleted(function ($dealer) {
            $dealer->salesOrderDealer()->delete();
            $dealer->adressDetail()->delete();
            $dealer->dealer_file()->delete();
            $dealer->distributorContract()->delete();
            $dealer->foreCast()->delete();
        });
    }

    public function scopeByNameOrOwnerOrDealerId($QQQ, $filter)
    {
        return $QQQ
            ->where("name", "like", "%" . $filter . "%")
            ->orWhere("dealer_id", "like", "%" . $filter . "%")
            ->orWhere("owner", "like", "%" . $filter . "%");
    }

    /**
     * delivery address
     */
    public function deliveryAddresses()
    {
        return $this->hasMany(DealerDeliveryAddress::class, "dealer_id", "id");
    }

    public function deliveryAddress()
    {
        return $this->hasOne(DealerDeliveryAddress::class, "dealer_id", "id");
    }

    public function contestParticiapant()
    {
        return $this->hasMany(ContestParticipant::class, "dealer_id", "id");
    }

    public function dealerTemp()
    {
        return $this->hasOne(DealerTemp::class, "dealer_id", "id");
    }

    public function nonRejectedDealerTemp()
    {
        return $this->hasOne(DealerTemp::class, "dealer_id", "id")
            ->whereNotIn("status", ["filed rejected", "change rejected"]);
    }

    public function scopeWithoutAppends($query)
    {
        self::$withoutAppends = true;

        return $query;
    }

    public function haveContestRunning()
    {
        return $this->hasOne(ContestParticipant::class, "dealer_id", "id")
            ->with('contest')
            ->where("participant_status", "4")
            ->whereHas('contest', function ($q) {
                $q->where('period_date_start', '<=', date('Y-m-d'))->where('period_date_end', '>=', date('Y-m-d'));
            });
    }

    public function activeContractContest()
    {
        return $this->hasOne(ContestParticipant::class, "dealer_id", "id")->activeContractStoreByDate($this->id, now());
    }

    public function dealer()
    {
        return $this->morphMany(DealerOrderRecapPerMonth::class, 'dealer');
    }

    public function recapOrder()
    {
        return $this->hasmany(DealerOrderRecapPerMonth::class, "dealer_id", "id")
            ->where("dealer_type", "dealer");
    }

    public function scopeUnmatchGradeWithSuggestedGrade($QQQ, $filter)
    {
        return $QQQ->whereColumn("grading_id", "<>", "suggested_grading_id");
    }

    public function stores()
    {
        return $this->hasMany(DiscountVoucher::class, 'store_id');
    }

}
