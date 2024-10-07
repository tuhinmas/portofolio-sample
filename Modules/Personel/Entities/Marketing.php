<?php

namespace Modules\Personel\Entities;

use Carbon\Carbon;
use App\Traits\Uuids;
use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Traits\DistributorTrait;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\DataAcuan\Entities\Region;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\DataAcuan\Entities\Position;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\SubDealer;
use Spatie\Activitylog\Contracts\Activity;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Marketing extends Model
{
    use HasFactory, Uuids, SoftDeletes, LogsActivity, ChildrenList, MarketingArea;
    use DistributorTrait;

    // use HasRelationships;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $guarded = [];
    protected $table = "personels";
    protected $appends = [
        'sub_region',
        'region',
        'total_sales_this_year',
        'total_sales_last_year',
        'total_sales_this_quartal',
        'total_sales_last_quartal',
        'count_store',
        'active_store',
        'dealer_count',
        'sub_dealer_count',
    ];

    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\MarketingFactory::new ();
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

    public function area()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id")->with("subRegionWithRegion");
    }

    public function subRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "id")->with("region");
    }

    public function supervisorSubRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "supervisor_id")->with("region");
    }

    public function region()
    {
        return $this->hasOne(Region::class, "personel_id", "id")->with("provinceRegion");
    }

    public function areaAplicator()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "applicator_id", "id")->with("subRegionWithRegion");
    }

    public function areaAplicators()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "applicator_id", "id")->with("subRegionWithRegion");
    }

    public function user()
    {
        return $this->belongsTo(User::class, "id", "personel_id")->withTrashed();
    }

    // public function notification()
    // {
    //     return $this->hasMany(Notification::class,)
    // }

    public function hasManyRegion()
    {
        return $this->belongsTo(Region::class, "id", "personel_id")->with("provinceRegion");
    }

    public function groupRmcFromDistrict()
    {
        return $this->hasOneThrough(
            SubRegion::class,
            MarketingAreaDistrict::class,
            "personel_id",
            "id",
            "id",
            "sub_region_id"
        )
            ->orderBy("marketing_area_sub_regions.name");
    }

    public function groupMdmFromDistrict()
    {
        return $this->hasOneDeepFromRelations($this->groupRmcFromDistrict(), (new SubRegion)->region());
    }

    public function groupMdmFromSubRegion()
    {
        return $this->hasOneThrough(
            Region::class,
            SubRegion::class,
            "personel_id",
            "id",
            "id",
            "region_id"
        )
            ->orderBy("marketing_area_regions.name");

    }

    public function position()
    {
        return $this->hasOne(Position::class, "id", "position_id");
    }

    public function getSubRegionAttribute()
    {
        $district = $this->area()->first();
        $sub = $this->subRegion()->first();
        $supervisor_sub_region = $this->supervisorSubRegion()->first();
        $sub_region = [];

        if ($district) {
            $district_list = $this->area()->get();
            foreach ($district_list as $district) {
                array_push($sub_region, $district->subRegionWithRegion);
            }
        } else if ($sub) {
            $sub_region = $this->subRegion()->get()->toArray();
        }
        $sub_region = collect($sub_region)->unique("name")->values();
        return $sub_region;
    }

    public function getRegionAttribute()
    {
        $district = $this->area()->first();
        $sub = $this->subRegion()->with("region")->first();
        $region_data = $this->hasManyRegion()->first();
        $region = [];

        if ($district) {
            $district = $this->area()->With([
                "subRegionWithRegion" => function ($QQQ) {
                    return $QQQ->with("region");
                },
            ])->get();
            $region = [];
            foreach ($district as $dist) {
                array_push($region, $dist->subRegionWithRegion->region);
            }
        } else if ($sub) {
            $sub = $this->subRegion()->with("region")->get();
            $region = [];
            foreach ($sub as $s) {
                array_push($region, $s->region);
            }
        } else if ($region_data) {
            $region = [];
            $region = $this->hasManyRegion()->get()->toArray();
        }

        $region = collect($region)->unique("name")->values();
        return $region;
    }

    public function getActiveStoreAttribute()
    {
        $active_dealers = $this->activeDealer()->active_dealer;
        $active_sub_dealers = $this->activeSubDealer()->active_sub_dealer;
        return $active_dealers + $active_sub_dealers;
    }

    public function getCountStoreAttribute()
    {
        $dealers = $this->dealer()->count();
        $sub_dealers = $this->subDealer()->count();
        return $dealers + $sub_dealers;
    }

    public function getDealerCountAttribute()
    {
        $dealers = $this->dealer()->count();
        return $dealers;
    }

    public function getSubDealerCountAttribute()
    {
        $sub_dealers = $this->subDealer()->count();
        return $sub_dealers;
    }

    /**
     * sales order
     *
     * @return void
     */
    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, "personel_id", "id");
    }

    /**
     * this year sales
     *
     * @return void
     */
    public function getTotalSalesThisYearAttribute()
    {
        $sales_orders = collect($this->salesYearly(Carbon::now()))
            ->reject(function ($order) {

                /* distributortrait */
                if ($this->isOrderInsideDistributorContract($order)) {
                    return $order;
                }
            });

        $total_indirect_sale_this_year = $sales_orders->where("type", "2")->all();
        $total_direct_sale_this_year = $sales_orders->where("type", "1")->all();

        $total_indirect_sale_this_year = collect($total_indirect_sale_this_year)->sum("total");
        $total_amount_this_year = 0;
        foreach ($total_direct_sale_this_year as $this_year) {
            if ($this_year->invoice) {
                $total_amount_this_year += $this_year->invoice->total;
            }
        }
        return $total_indirect_sale_this_year + $total_amount_this_year;
    }

    public function salesYearly($year)
    {
        $sales_orders = SalesOrder::query()
            ->with([
                "invoice",
                "dealer" => function ($QQQ) {
                    return $QQQ->with([
                        "ditributorContract",
                    ]);
                },
            ])
            ->where("sales_orders.personel_id", $this->id)
            ->where("sales_orders.status", "confirmed")
            ->where(function ($QQQ) use ($year) {
                return $QQQ
                    ->where(function ($QQQ) use ($year) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                return $QQQ->whereYear("created_at", $year);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($year) {
                        return $QQQ
                            ->where("type", "2")
                            ->whereYear("date", $year);
                    });
            })
            ->get();

        return $sales_orders;
    }

    /**
     * last year sales
     *
     * @return void
     */
    public function getTotalSalesLastYearAttribute()
    {
        $sales_orders = collect($this->salesYearly(Carbon::now()->subYear()))
            ->reject(function ($order) {

                /* distributortrait */
                if ($this->isOrderInsideDistributorContract($order)) {
                    return $order;
                }
            });
            
        $total_indirect_sale_this_year = $sales_orders->where("type", "2")->all();
        $total_direct_sale_this_year = $sales_orders->where("type", "1")->all();

        $total_indirect_sale_this_year = collect($total_indirect_sale_this_year)->sum("total");
        $total_amount_this_year = 0;
        foreach ($total_direct_sale_this_year as $this_year) {
            if ($this_year->invoice) {
                $total_amount_this_year += $this_year->invoice->total;
                // if ($this_year->invoice->payment_status == "settle") {
                //     $total_amount_this_year += $this_year->invoice->total;
                // }
            }
        }
        return $total_indirect_sale_this_year + $total_amount_this_year;
    }

    /**
     * total sales this quartal sales
     *
     * @return void
     */
    public function getTotalSalesThisQuartalAttribute()
    {
        $last_three_month = Carbon::now()->startOfQuarter();
        $this_month = Carbon::now();
        $sales_orders = SalesOrder::query()
            ->with([
                "invoice",
                "dealer" => function ($QQQ) {
                    return $QQQ->with([
                        "ditributorContract",
                    ]);
                },
            ])
            ->where("sales_orders.personel_id", $this->id)
            ->where("sales_orders.status", "confirmed")
            ->whereBetween("sales_orders.created_at", [$last_three_month, $this_month])
            ->get()
            ->reject(function ($order) {

                /* distributortrait */
                if ($this->isOrderInsideDistributorContract($order)) {
                    return $order;
                }
            });

        $total_indirect_sale_this_quartal = $sales_orders->where("type", "2")->all();
        $total_direct_sale_this_quartal = $sales_orders->where("type", "1")->all();

        $total_indirect_sale_this_quartal = collect($total_indirect_sale_this_quartal)->sum("total");
        $total_amount_this_quartal = 0;
        foreach ($total_direct_sale_this_quartal as $this_quartal) {
            if ($this_quartal->invoice) {
                $total_amount_this_quartal += $this_quartal->invoice->total;
            }
        }
        return $total_indirect_sale_this_quartal + $total_amount_this_quartal;
    }

    /**
     * total sales last quartal
     *
     * @return void
     */
    public function getTotalSalesLastQuartalAttribute()
    {
        $last_three_month = Carbon::now()->subQuarter()->startOfQuarter();
        $this_month = Carbon::now()->subQuarter()->endOfQuarter();
        $sales_orders = SalesOrder::query()
            ->with([
                "invoice",
                "dealer" => function ($QQQ) {
                    return $QQQ->with([
                        "ditributorContract",
                    ]);
                },
            ])->where("sales_orders.personel_id", $this->id)
            ->where("sales_orders.status", "confirmed")
            ->whereBetween("sales_orders.created_at", [$last_three_month, $this_month])
            ->get()
            ->reject(function ($order) {

                /* distributortrait */
                if ($this->isOrderInsideDistributorContract($order)) {
                    return $order;
                }
            });

        $total_indirect_sale_this_quartal = $sales_orders->where("type", "2")->all();
        $total_direct_sale_this_quartal = $sales_orders->where("type", "1")->all();

        $total_indirect_sale_this_quartal = collect($total_indirect_sale_this_quartal)->sum("total");
        $total_amount_this_quartal = 0;
        foreach ($total_direct_sale_this_quartal as $this_quartal) {
            if ($this_quartal->invoice) {
                $total_amount_this_quartal += $this_quartal->invoice->total;
            }
        }
        return $total_indirect_sale_this_quartal + $total_amount_this_quartal;
    }

    public function dealer()
    {
        return $this->hasMany(DealerV2::class, "personel_id", "id");
    }

    public function dealerv2()
    {
        return $this->hasMany(DealerV2::class, "personel_id", "id");
    }

    public function subDealer()
    {
        return $this->hasMany(SubDealer::class, "personel_id", "id");
    }

    public function store()
    {
        return $this->hasMany(Store::class, "personel_id", "id");
    }

    public function storeTransferedandAccepted()
    {
        return $this->hasMany(Store::class, "personel_id", "id")->whereIn("status", ["transfered", "accepted"]);
    }

    public function getIdPersonelAttribute()
    {
        return $this->id;

    }

    public function storeOnlyDistrict()
    {
        return $this->hasMany(Store::class, "personel_id", "id");
    }

    public function coreFarmerHasMany()
    {
        return $this->hasManyThrough(
            CoreFarmer::class,
            Store::class,
            'personel_id',
            'store_id',
            'id',
            'id'
        );
    }

    public function storeCoreFarmerMore3()
    {
        return $this->hasMany(Store::class, "personel_id", "id")->whereHas('core_farmer', function ($QQQ) {
            return $QQQ->whereIn("status", ["transfered", "accepted"])->having('id', '>', 3);;

        });

    }

    public function activeDealer()
    {
        $active_dealer = DB::table('dealers')
            ->leftJoin("sales_orders as s", "s.store_id", "=", "dealers.id")
            ->where("s.created_at", ">=", Carbon::now()->subDays(365))
            ->where("dealers.personel_id", $this->id)
            ->select(DB::raw('count(distinct dealers.id) as active_dealer'))
            ->first();
        return $active_dealer;
    }

    public function getActiveDealerAttribute()
    {
        //$active_dealer = DealerV2::whereHas('salesOrders')->get();

        $sales_orders = DealerV2::whereHas('salesOrders', function ($q) {
            return $q->where(function ($parameter) {
                return $parameter
                    ->where("type", "1")
                    ->whereHas("invoice", function ($QQQ) {
                        return $QQQ->where("created_at", ">=", Carbon::now()->subDays(365));
                    });
            })
                ->orWhere(function ($parameter) {
                    return $parameter
                        ->where("type", "2")
                        ->where("date", ">=", Carbon::now()->subDays(365));
                });
        })->where('personel_id', $this->id)->get();
        $directCount = $sales_orders->count();
        return $directCount;
    }

    public function getInactiveActiveDealerAttribute()
    {
        //$active_dealer = DealerV2::whereHas('salesOrders')->get();

        $sales_orders = DealerV2::whereHas('salesOrders', function ($q) {
            return $q
                ->where(function ($parameter) {
                    return $parameter
                        ->where("type", "1")
                        ->whereHas("invoice", function ($QQQ) {
                            return $QQQ->where("created_at", "<=", Carbon::now()->subDays(365));
                        });
                })
                ->orWhere(function ($parameter) {
                    return $parameter
                        ->where("type", "2")
                        ->where("date", "<=", Carbon::now()->subDays(365));
                });
        })->where('personel_id', $this->id)->get();
        $directCount = $sales_orders->count();
        return $directCount;
    }

    public function activeSubDealer()
    {
        $active_sub_dealer = DB::table('sub_dealers')
            ->leftJoin("sales_orders as s", "s.store_id", "=", "sub_dealers.id")
            ->where("s.created_at", ">=", Carbon::now()->subDays(365))
            ->where("sub_dealers.personel_id", $this->id)
            ->select(DB::raw('count(distinct sub_dealers.id) as active_sub_dealer'))
            ->first();
        return $active_sub_dealer;
    }

    /**
     * scope list marketing on target list
     *
     * @param [type] $QQQ
     * @return void
     */
    public function scopeListOnTarget($QQQ)
    {
        $marketing_active = DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->whereNotNull("personel_id")
            ->get()
            ->pluck("personel_id");

        $personels = self::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->get()
            ->pluck("id");

        $personel_id = collect($marketing_active)
            ->merge($personels)
            ->unique()
            ->toArray();

        return $QQQ->whereIn("personels.id", $personel_id);
    }

    public function districtHasOne()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id");
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
                $target = $districts->subRegionWithRegion->target;
            } else if ($sub_region) {
                $target = $sub_region->target;
            } else if ($region) {
                $target = $region->target;
            }
        }
        return $target;
    }

    public function scopeMarketingHasArea($QQQ)
    {
        $personels = self::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->get()
            ->pluck("id");

        return $QQQ->where(function ($QQQ) use ($personels) {
            return $QQQ
                ->where(function ($QQQ) {
                    return $QQQ
                        ->whereHas("districtHasOne")
                        ->orWhereHas("subRegion")
                        ->orWhereHas("region");
                })
                ->orWhereIn("id", $personels);
        });
    }

    public function scopeAsSupervisor($query, $personel_id = null)
    {
        $personel_list = $this->getChildren(auth()->user()->personel_id);
        if (auth()->user()->hasAnyRole(
            'administrator',
            'super-admin',
            'Marketing Support',
            'Marketing Manager (MM)',
            'Sales Counter (SC)',
            'Operational Manager',
            'Support Bagian Distributor',
            'Support Bagian Kegiatan',
            'Support Distributor',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
        )) {
            return $query;
        }
        return $query->whereIn("id", $personel_list);
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("id", $marketing_on_branch);
    }

    public function scopeByNameRegionSubRegion($query, $param)
    {
        $region_id = DB::table('marketing_area_regions')
            ->whereNull("deleted_at")
            ->where("name", "like", "%" . $param . "%")
            ->pluck("id")
            ->toArray();

        $sub_region_id = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->where("name", "like", "%" . $param . "%")
            ->pluck("id")
            ->toArray();

        $area_id = array_merge($region_id, $sub_region_id);
        $marketing_area = $this->marketingListByAreaListId($area_id);
        return $query
            ->where("name", "like", "%" . $param . "%")
            ->orWhereIn("id", $marketing_area);
    }
}
