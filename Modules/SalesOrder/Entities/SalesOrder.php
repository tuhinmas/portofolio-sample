<?php

namespace Modules\SalesOrder\Entities;

use App\Models\User;
use App\Traits\Enums;
use App\Traits\Uuids;
use App\Models\ActivityLog;
use App\Traits\MarketingArea;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealer\Entities\SubDealer;
use Spatie\Activitylog\Contracts\Activity;
use Modules\KiosDealerV2\Entities\DealerV2;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\SalesOrderV2\Entities\FeeSharing;
use Modules\SalesOrder\Traits\ScopeSalesOrder;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\Contest\Entities\ContestPointOrigin;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\SalesOrder\Entities\LogStatusFeeOrder;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Builder\FeeMarketingBuilder;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Pricecurrent\LaravelEloquentFilters\Filterable;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrder\Traits\ScopeSalesOrderContest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Traits\ScopeSalesOrderDistributor;
use Modules\SalesOrder\Entities\SalesOrderStatusFeeShould;
use Modules\SalesOrder\Traits\ScopeSalesOrderIndirectSales;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;

class SalesOrder extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use ScopeSalesOrderIndirectSales;
    use ScopeSalesOrderDistributor;
    use ScopeSalesOrderContest;
    use CascadeSoftDeletes;
    use SuperVisorCheckV2;
    use ScopeSalesOrder;
    use MarketingArea;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Filterable;
    use Uuids;
    use Enums;

    public $incrementing = false;
    protected $guarded = [];
    protected $cascadeDeletes = [
        "feeTargetSharingOrigin",
        "sales_order_detail",
        "contestPointOrigin",
        "salesOrderOrigin",
        "feeSharingOrigin",
        "invoiceHasOne",
        "feeSharing",
    ];

    protected $enumSalesModes = ["office", "follow_up", "marketing", null];

    protected $dates = ['deleted_at'];

    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\SalesOrderFactory::new ();
    }

    /**
     * activity logs set causer
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    /**
     * activity logs
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }

    public function sales_order_detail()
    {
        return $this->hasMany(SalesOrderDetail::class, 'sales_order_id', 'id')->with("product", "pointProductAllYear", "salesOrderOrigin");
    }

    public function salesOrderDetail()
    {
        return $this->hasMany(SalesOrderDetail::class, 'sales_order_id', 'id')->with("product", "pointProductAllYear", "salesOrderOrigin");
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id')->withTrashed();
    }
    public function dealer()
    {
        return $this->hasOne(Dealer::class, 'id', 'store_id')->with("agencyLevel")->withTrashed();
    }

    public function dealerv2()
    {
        return $this->hasOne(DealerV2::class, 'id', 'store_id')->with("agencyLevel")->withTrashed();
    }

    public function subDealer()
    {
        return $this->hasOne(SubDealer::class, 'id', 'store_id')->withTrashed();
    }

    public function distributor()
    {
        return $this->hasOne(Dealer::class, 'id', 'distributor_id')->with("agencyLevel")->withTrashed();
    }

    public function user()
    {
        return $this->hasMany(User::class, 'user_id', 'id');
    }

    public function confirmedBy()
    {
        return $this->hasOne(Personel::class, 'id', 'confirmed_id')->withTrashed();
    }

    public function confirmedHistory()
    {
        return $this->hasOne(SalesOrderHistoryChangeStatus::class, 'sales_order_id', 'id')
            ->where("status", "confirmed")
            ->orderByDesc("created_at");
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id")->with("position")->withTrashed();
    }

    public function personelBelong()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id")->withTrashed()->with("position")->withTrashed();
    }

    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class, 'id', 'payment_method_id')->withTrashed();
    }

    // public function paymentMethodWith7Days()
    // {
    //     return $this->hasOne(PaymentMethod::class, 'id', 'payment_method_id')->withTrashed();
    // }

    public function statusFeeShould()
    {
        return $this->hasOne(SalesOrderStatusFeeShould::class, "sales_order_id", "id");
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id', 'sales_order_id')->with("user", "payment")->orderBy("created_at", "desc");
    }

    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class,
            'sales_order_id',
            'invoice_id',
            'id',
            'id',
        );
    }

    public function invoiceHasOne()
    {
        return $this->belongsTo(Invoice::class, 'id', 'sales_order_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'sales_order_id', 'id')->where('payment_status', "settle");
    }
    public function sales_order_detail_product()
    {
        return $this->hasOne(SalesOrderDetail::class, 'sales_order_id', 'id')->with("product")->withTrashed();
    }

    public function salesCounter()
    {
        return $this->hasOne(Personel::class, "id", "counter_id")->withTrashed();
    }

    public function salesOrderHistoryChange()
    {
        return $this->hasOne(SalesOrderHistoryChangeStatus::class, "sales_order_id", "id")->where("status", "canceled")->orderByDesc("created_at");
    }
   
    public function salesOrderHistoryChangeStatus()
    {
        return $this->hasOne(SalesOrderHistoryChangeStatus::class, "sales_order_id", "id");
    }

    public function salesOrderHistoryChangeLast()
    {
        return $this->hasOne(SalesOrderHistoryChangeStatus::class, "sales_order_id", "id")->orderByDesc("created_at");
    }

    public function followUpBy()
    {
        return $this->hasOne(Personel::class, "id", "counter_id")->withTrashed();
    }

    public function scopeSupervisor($query)
    {
        $personel_id = $this->getPersonel();

        /* get dealer id list */
        $dealers = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $personel_id)->pluck("id")->toArray();

        /* get sub dealer id list */
        $sub_dealers = DB::table('sub_dealers')->whereNull("deleted_at")->whereIn("personel_id", $personel_id)->pluck("id")->toArray();

        $stores = array_unique(array_merge($dealers, $sub_dealers));

        return $query->whereIn("store_id", $stores);
    }

    public function scopeUnsettlePayment($QQQ)
    {
        return $QQQ->whereHas("invoice", function ($QQQ) {
            return $QQQ->where("payment_status", "!=", "settle");
        });
    }

    /**
     * scope region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */
    public function scopeRegion($QQQ, $region_id)
    {
        $marketing_list = $this->marketingListByAreaId($region_id);
        return $QQQ->whereIn("personel_id", $marketing_list);
    }

    /**
     * scope sub region
     *
     * @param [type] $QQQ
     * @param [type] $sub_region_id
     * @return void
     */
    public function scopeSubRegion($QQQ, $sub_region_id)
    {
        $marketing_list = $this->marketingListByAreaId($sub_region_id);
        return $QQQ->whereIn("personel_id", $marketing_list);
    }

    public function scopeExcludeBlockedDealer($query)
    {
        return $query
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereHas("dealerV2")
                    ->orWhereHas('subDealer');
            });
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    public function scopeByProformaDealerIdDealerName($query, $parameter)
    {
        return $query
            ->where(function ($QQQ) use ($parameter) {
                return $QQQ
                    ->where(function ($QQQ) use ($parameter) {
                        return $QQQ
                            ->whereHas("invoiceHasOne", function ($QQQ) use ($parameter) {
                                return $QQQ->where("invoice", "like", "%" . $parameter . "%");
                            });
                    })
                    ->orWhere(function ($QQQ) use ($parameter) {
                        return $QQQ->whereHas("dealerv2", function ($QQQ) use ($parameter) {
                            return $QQQ
                                ->where("dealer_id", $parameter)
                                ->orWhere("name", "like", "%" . $parameter . "%")
                                ->orWhere("owner", "like", "%" . $parameter . "%");
                        });
                    });
            });
    }

    public function scopeDirectSaleByDate($query, $date)
    {
        return $query
            ->whereHas("invoice", function ($QQQ) use ($date) {
                return $QQQ->whereDate("created_at", $date);
            })
            ->orWhere(function ($QQQ) use ($date) {
                return $QQQ
                    ->whereNull("link")
                    ->whereDoesntHave("invoice")
                    ->whereDate("updated_at", $date);
            });
    }

    public function scopeIndirectSaleByDate($query, $date)
    {
        return $query->where(function ($QQQ) use ($date) {
            return $QQQ
                ->whereNotNull("link")
                ->whereDoesntHave("invoice")
                ->whereDate("date", $date);
        });
    }

    /**
     * scope fee marketing total
     *
     * @param [type] $query
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function newEloquentBuilder($query): FeeMarketingBuilder
    {
        return new FeeMarketingBuilder($query);
    }

    public function scopeDistributorPickup($query)
    {
        return $query
            ->where(function ($QQQ) use ($request) {
                return $QQQ
                    ->where("store_id", $request->dealer_id)
                    ->whereIn("status", ["confirmed", "returned", "pending"]);
            })
            ->where(function ($QQQ) use ($request, $active_contract) {
                return $QQQ
                    ->where(function ($QQQ) use ($active_contract) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($active_contract) {
                                return $QQQ->whereDate("created_at", ">=", $active_contract->contract_start);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($active_contract) {
                        return $QQQ
                            ->where("type", "2")
                            ->whereDate("created_at", ">=", $active_contract->contract_start);
                    });
            });
    }

    public function scopePendingDistributorSalesDuringContract($query, $active_contract, $product_id)
    {
        return $query
            ->where("distributor_id", $active_contract->dealer_id)
            ->where("type", "2")
            ->whereDate("created_at", ">=", $active_contract->contract_start)
            ->whereDate("created_at", "<=", $active_contract->contract_end)
            ->where("status", "confirmed")
            ->whereHas("sales_order_detail", function ($QQQ) use ($product_id) {
                return $QQQ
                    ->where("product_id", $product_id);
            });
    }

    public function logConfirmation()
    {
        return $this->hasMany(ActivityLog::class, "subject_id", "id")
            ->orderBy("created_at", "desc")
            ->select(DB::raw("activity_log.*, JSON_UNQUOTE(json_extract(activity_log.properties, '$.attributes.status')) as status_change"));
    }

    public function logConfirmationCanceled()
    {
        return $this->hasMany(ActivityLog::class, "subject_id", "id")
            ->where("description", "updated")
            ->whereIn("properties->attributes->status", ["canceled"])
            ->orderBy("created_at", "desc")
            ->select(DB::raw("activity_log.*, JSON_UNQUOTE(json_extract(activity_log.properties, '$.attributes.status')) as status_chenge"));
    }

    public function feeSharing()
    {
        return $this->hasMany(FeeSharing::class, "sales_order_id", "id");
    }

    public function feeSharingOrigin()
    {
        return $this->hasMany(FeeSharingSoOrigin::class, "sales_order_id", "id");
    }

    public function feeTargetSharingOrigin()
    {
        return $this->hasMany(FeeTargetSharingSoOrigin::class, "sales_order_id", "id");
    }

    public function logWorkerPointMarketing()
    {
        return $this->hasOne(LogWorkerPointMarketing::class, 'sales_order_id', 'id');
    }

    public function logWorkerPointMarketingActive()
    {
        return $this->hasOne(LogWorkerPointMarketingActive::class, 'sales_order_id', 'id');
    }

    public function logWorkerSalesPoint()
    {
        return $this->hasOne(LogWorkerSalesPoint::class, 'sales_order_id', 'id');
    }

    public function logWorkerSalesOderDetailMarketingFee()
    {
        return $this->hasMany(LogWorkerSalesFee::class, 'sales_order_id', 'id')->where('type', 1);
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, "id", "grading_id");
    }

    public function invoiceOnly()
    {
        return $this->hasOne(Invoice::class, 'sales_order_id', 'id');
    }

    public function returnedBy()
    {
        return $this->belongsTo(Personel::class, "returned_by", "id")->withTrashed();
    }

    public function statusFee()
    {
        return $this->hasOne(StatusFee::class, "id", "status_fee_id");
    }

    public function logWorkerDirectFee()
    {
        return $this->hasOne(LogWorkerSalesFee::class, "sales_order_id", "id");
    }

    public function lastReceivingGoodIndirect()
    {
        return $this->hasOne(ReceivingGoodIndirectSale::class, 'sales_order_id', 'id')->orderBy("date_received", "desc");
    }

    public function salesOrderOrigin()
    {
        return $this->hasMany(SalesOrderOrigin::class, "sales_order_id", "id");
    }

    public function contestPointOrigin()
    {
        return $this->hasManyThrough(
            ContestPointOrigin::class,
            SalesOrderDetail::class,
            "sales_order_id",
            "sales_order_details_id",
            "id",
            "id"
        );
    }

    public function firstDeliveryOrder()
    {
        return $this->hasOneDeepFromRelations($this->invoiceHasOne(), (new Invoice())->firstDeliveryOrder());
    }

    public function deliveryOrders()
    {
        return $this->hasManyDeepFromRelations($this->invoiceHasOne(), (new Invoice())->deliveryOrders());
    }

    public function logStatusFeeOrder()
    {
        return $this->hasOne(LogStatusFeeOrder::class, "sales_order_id", "id");
    }

    public function logMarketingFeeCounter()
    {
        return $this->hasOne(LogMarketingFeeCounter::class, "sales_order_id", "id");
    }

    /*
    |-------------------
    | SCOPE LIST
    |--------------
     */

    public function scopeConfirmedOrderByYear($query, $year)
    {
        $table_name = self::getTable();
        return $query
            ->where(function ($QQQ) use ($table_name, $year) {
                return $QQQ
                    ->where(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "2")
                            ->whereIn("{$table_name}.status", ["confirmed"])
                            ->whereYear("{$table_name}.created_at", $year);
                    })
                    ->orWhere(function ($QQQ) use ($table_name, $year) {
                        return $QQQ
                            ->where("{$table_name}.type", "1")
                            ->where("{$table_name}.status", "confirmed")
                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                return $QQQ->whereYear("created_at", $year);
                            });
                    });
            });
    }

    public function addressDetailDeep()
    {
        return $this->hasOneDeepFromRelations($this->dealer(), (new Dealer())->adressDetail())
            ->where("address_with_details.type", "dealer");

    }
}
