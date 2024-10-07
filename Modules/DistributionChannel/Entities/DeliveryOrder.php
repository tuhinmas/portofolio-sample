<?php

namespace Modules\DistributionChannel\Entities;

use Carbon\Carbon;
use App\Traits\Uuids;
use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\KiosDealerV2\Entities\DealerV2;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\ReceiptDetail;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\DistributionChannel\Traits\ScopeDeliveryOrder;
use Modules\PickupOrder\Constants\DeliveryPickupOrderStatus;
use Modules\PromotionGood\Entities\PromotionGoodDispatchOrder;

class DeliveryOrder extends Model
{
    use CascadeSoftDeletes;
    use ScopeDeliveryOrder;
    use MarketingArea;
    use ChildrenList;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $guarded = [];
    public $appends = [
        "date_received",
    ];
    protected $cascadeDeletes = [
        "receivingGoods",
    ];
    protected $dates = ['deleted_at'];

    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\DeliveryOrderFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    public function getDateReceivedAttribute()
    {
        $receving_good = $this->receivingGoodHasReceived()->first();
        return $receving_good ? Carbon::parse($receving_good->date_received)->format("Y-m-d") : null;
    }

    public function dispatchOrder()
    {
        return $this->hasOne(DispatchOrder::class, "id", "dispatch_order_id");
    }

    public function dispatchOrderDetails()
    {
        return $this->hasMany(DispatchOrderDetail::class, "dispatch_order_id", "id_dispatch_order");
    }

    public function promotionGoodDispatchOrder()
    {
        return $this->hasMany(PromotionGoodDispatchOrder::class, "delivery_order_id", "id");
    }

    public function receiptDetail()
    {
        return $this->hasOne(ReceiptDetail::class, "id", "receipt_detail_id");
    }

    public function receivingGoods()
    {
        return $this->hasOne(ReceivingGood::class, "delivery_order_id", "id")->orderBy("date_received", "desc");
    }

    public function receivingGoodHasReceived()
    {
        return $this
            ->hasOne(ReceivingGood::class, "delivery_order_id", "id")
            ->orderBy("date_received", "desc")
            ->where("delivery_status", "2");
    }

    public function receivingGoodDetailHasReceived()
    {
        return $this->hasManyThrough(
            ReceivingGoodDetail::class,
            ReceivingGood::class,
            "delivery_order_id",
            "receiving_good_id",
            "id",
            "id"
        )
            ->where("receiving_good_details.status", "delivered")
            ->where("receiving_goods.delivery_status", "2");
    }

    public function receipt()
    {
        return $this->hasOne(ProformaReceipt::class, "id", "receipt_id")->where("receipt_for", "4");
    }

    public function scopeHasReceivingGoods($query, $name, $date_received_start = null, $date_received_end = null)
    {
        if ($name == 'yes') {
            return $query->whereHas("receivingGoods", function ($QQQ) use ($date_received_start, $date_received_end) {
                return $QQQ
                    ->whereHas("receivingGoodDetail")
                    ->where("delivery_status", "2")
                    ->when($date_received_start && $date_received_end, function ($QQQ) use ($date_received_start, $date_received_end) {
                        return $QQQ
                            ->whereDate("date_received", ">=", $date_received_start)
                            ->whereDate("date_received", "<=", $date_received_end);
                    });
            });
        } else {
            return $query
                ->where(function ($q) {
                    $q
                        ->where(function ($q) {
                            $q
                                ->whereHas('dispatchOrder', function ($q) {
                                    $q->where('type_driver', 'internal');
                                });

                            /**
                             * PENDING AT THE MOMENT
                             * need to check if delivery order doent have pickup order
                             *
                             * ->has('deliveryPickupOrders');
                             */
                        })
                        ->orWhere(function ($q) {
                            $q
                                ->whereHas('dispatchOrder', function ($q) {
                                    $q->where('type_driver', '!=', 'internal');
                                })
                                ->orWhereNotNull('dispatch_promotion_id');
                        });
                })
                ->whereDoesntHave('receivingGoods', function ($QQQ) {
                    return $QQQ
                        ->whereHas("receivingGoodDetail")
                        ->where("delivery_status", "2");
                });
        }
    }

    /**
     * filter by dealer name
     *
     * @param [type] $query
     * @param [type] $dealer_name
     * @return void
     */
    public function scopeByDealerName($query, $dealer_name)
    {
        return $query->whereHas("dispatchOrder", function ($QQQ) use ($dealer_name) {
            return $QQQ->whereHas("invoice", function ($QQQ) use ($dealer_name) {
                return $QQQ->whereHas("salesOrderOnly", function ($QQQ) use ($dealer_name) {
                    return $QQQ->whereHas("dealer", function ($QQQ) use ($dealer_name) {
                        return $QQQ->where("name", "like", "%" . $dealer_name . "%");
                    });
                });
            });
        });
    }

    public function scopeByPickupNumber($query, $pickupOrder)
    {
        return $query->whereHas("dispatchOrder.pickupOrder", function ($QQQ) use ($pickupOrder) {
            return $QQQ->where("pickup_number", "like", "%" . $pickupOrder . "%");
        });
    }

    public function scopeDeliveryCanceled($query)
    {
        return $query->where("status", "canceled");
    }

    /**
     * delivery order by marketing id
     *
     * @param [type] $query
     * @param [type] $personel_id
     * @return void
     */
    public function scopeByMarketingId($query, $personel_id)
    {
        return $query->where(function ($q) use ($personel_id) {
            return $q
                ->whereHas("dispatchOrder", function ($QQQ) use ($personel_id) {
                    return $QQQ->whereHas("invoice", function ($QQQ) use ($personel_id) {
                        return $QQQ->whereHas("salesOrderOnly", function ($QQQ) use ($personel_id) {
                            return $QQQ->whereHas("dealer", function ($QQQ) use ($personel_id) {
                                return $QQQ->where("personel_id", $personel_id);
                            });
                        });
                    });
                })
                ->orWhere(function ($q) use ($personel_id) {
                    $q
                        ->wherehas('dispatchPromotion', function ($q) use ($personel_id) {
                            $q->whereHas('promotionGoodRequest', function ($q) use ($personel_id) {
                                $personel = Personel::where('supervisor_id', $personel_id)->select('id')->get()->pluck('id')->toArray();
                                $q->where('created_by', $personel_id);
                                if ($personel) {
                                    $q->orWhereIn('created_by', $personel);
                                }
                            });
                        });

                    /**
                     * PENDING AT THE MOMENT
                     * need to check if delivery order doent have pickup order
                     *
                     * ->whereHas('deliveryPickupOrders', function ($q) {
                     *     $q->where('status', DeliveryPickupOrderStatus::NOT_RECEIVED);
                     * });
                     */
                });
        });
    }

    public function scopeSupervisor($query, $personel_id)
    {
        $personel_id_list = $this->getChildren($personel_id);
        return $query->whereHas("dispatchOrder", function ($QQQ) use ($personel_id_list) {
            return $QQQ->whereHas("invoice", function ($QQQ) use ($personel_id_list) {
                return $QQQ->whereHas("salesOrderOnly", function ($QQQ) use ($personel_id_list) {
                    return $QQQ->whereHas("dealer", function ($QQQ) use ($personel_id_list) {
                        return $QQQ->whereIn("personel_id", $personel_id_list);
                    });
                });
            });
        });
    }

    public function dealer()
    {
        return $this->hasOne(DealerV2::class, "id", "dealer_id")->withTrashed();
    }

    public function marketing()
    {
        return $this->hasOne(Personel::class, "id", "marketing_id")->withTrashed();
    }

    public function createdBy()
    {
        return $this->hasOne(Personel::class, "id", "created_by")->withTrashed();
    }

    public function scopeByDealerNameDealerOwnerCustIdDistrictCityProvince($query, $seacrh)
    {
        return $query
            ->whereHas("dealer", function ($QQQ) use ($seacrh) {
                return $QQQ
                    ->withTrashed()
                    ->where("name", "like", "%" . $seacrh . "%")
                    ->orWhere("owner", "like", "%" . $seacrh . "%")
                    ->orWhere("dealer_id", $seacrh)
                    ->orWhereHas("addressDetail", function ($QQQ) use ($seacrh) {
                        return $QQQ
                            ->whereType("dealer")
                            ->where(function ($QQQ) use ($seacrh) {
                                return $QQQ
                                    ->whereHas("province", function ($QQQ) use ($seacrh) {
                                        return $QQQ->where("name", "like", "%" . $seacrh . "%");
                                    })
                                    ->orWhereHas("city", function ($QQQ) use ($seacrh) {
                                        return $QQQ->where("name", "like", "%" . $seacrh . "%");
                                    })
                                    ->orWhereHas("district", function ($QQQ) use ($seacrh) {
                                        return $QQQ->where("name", "like", "%" . $seacrh . "%");
                                    });
                            });
                    });
            })
            ->whereHas("dispatchOrder", function ($QQQ) {
                return $QQQ->whereHas("invoice", function ($QQQ) {
                    return $QQQ->whereHas("salesOrderOnly");
                });
            });
    }

    /**
     * personel branch
     */
    public function scopePersonelBranch($query, $personel_id = null)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", ($personel_id ? $personel_id : auth()->user()->personel_id))->pluck("region_id");

        /* get marketing on branch */
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);

        /* get dealer marketing on branch, to get only marketing which handle dealer today */
        $personel_dealer = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $marketing_on_branch)->pluck("personel_id");

        /* get sales order by personel */
        $sales_orders_id = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $personel_dealer)->pluck("id");

        return $query
            ->whereHas("dealer", function ($QQQ) use ($marketing_on_branch) {
                return $QQQ->whereIn("personel_id", $marketing_on_branch);
            })
            ->whereHas("dispatchOrder", function ($QQQ) {
                return $QQQ
                    ->whereHas("invoice", function ($QQQ) {
                        return $QQQ
                            ->whereHas("salesOrderOnly");
                    });
            });
    }

    public function promotionDispatchOrders()
    {
        return $this->hasMany(PromotionGoodDispatchOrder::class, "delivery_order_id", "id");
    }

    public function dispatchPromotion()
    {
        return $this->hasOne(DispatchPromotion::class, "id", "dispatch_promotion_id");
    }

    public function deliveryPickupOrders()
    {
        return $this->hasMany(DeliveryPickupOrder::class, "delivery_order_id", "id");
    }

    public function dispatch()
    {
        return $this->dispatchOrder() ?? $this->dispatchPromotion();
    }
}
