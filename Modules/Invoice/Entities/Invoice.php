<?php

namespace Modules\Invoice\Entities;

// use Illuminate\Http\Request;
use App\Traits\ChildrenList;
use App\Traits\DistributorTrait;
use App\Traits\MarketingArea;
use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\Ppn;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Invoice\Entities\EntrusmentPayment;
use Modules\Invoice\Entities\InvoiceProforma;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Traits\ScopeInvoice;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\Traits\CustomPersonnelLogic;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\Voucher\Entities\RedeemedVoucher;
use Riskihajar\Terbilang\Facades\Terbilang;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Invoice extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use HasRelationships;
    use CascadeSoftDeletes;
    use SuperVisorCheckV2;
    use DistributorTrait;
    use MarketingArea;
    use ChildrenList;
    use LogsActivity;
    use ScopeInvoice;
    use SoftDeletes;
    use HasFactory;
    use Uuids;
    use CustomPersonnelLogic;

    protected $casts = [
        "id" => "string",
    ];

    protected $cascadeDeletes = [
        "allCreditMemoDestination",
        "allCreditMemoOrigins",
        "entrusmentPayment",
        "invoiceProforma",
        "dispatchOrder",
        "allPayment",
        "hasReceived",
    ];
    protected $dates = ['deleted_at'];

    protected $appends = [
        'nominal',
        'last_payment',
        'remaining',
        'total_invoice',
        'payment_time',
        'total_invoice_terbilang',
        // 'payment_due_from_attribute',
        // 'payment_due',
        'status',
        "is_editable",
        // "first_delivery_order_date",
    ];

    protected $guarded = [];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\InvoiceFactory::new ();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
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
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }

    protected static function booted()
    {
        parent::boot();
        static::creating(function ($invoice) {
            if ($invoice->invoice == null) {
                $invoice->invoice = Carbon::now();
            }
        });
    }

    public function getIsEditableAttribute()
    {
        /**
         * new rule according 2024-01-10
         * proforma can not be deleted if there
         * 1. active dispatch order
         */
        $is_deletable = true;
        if ($this->dispatchOrder()->count() > 0) {
            $is_deletable = $this->dispatchOrder()
                ->get()
                ->filter(fn($dispatch) => $dispatch->is_active == true)
                ->count() == 0;
        }

        if ($this->invoiceProforma()->first()) {
            $is_deletable = false;
        }

        return $is_deletable;

        /**
         * PENDING
         */
        $invoice_proforma = $this->invoiceProforma()->first();
        $delivery_order = $this->dispatchOrder()->get();
        $delivery_order = collect($delivery_order)
            ->filter(fn($dispatch) => $dispatch->deliveryOrder)
            ->sortBy(function ($dispatch) {
                return $dispatch->deliveryOrder->date_delivery;
            })
            ->filter(function ($dispatch) {
                return Carbon::parse($dispatch->deliveryOrder->date_delivery)->format("Y-m-d") <= now()->format("Y-m-d");
            })
            ->first();

        $receiving_good = $this->hasReceived()->first();

        if ($invoice_proforma) {
            return false;
        } elseif ($delivery_order) {
            if ($delivery_order->deliveryOrder) {
                if (Carbon::parse($delivery_order->deliveryOrder->date_delivery)->format("Y-m-d") >= Carbon::now()->format("Y-m-d")) {
                    return false;
                }
            }
        }

        /* has received */
        if ($receiving_good) {
            return false;
        }

        return true;
    }

    /**
     * invoice per dealer
     */
    public function scopeInvoicePerDealer($query, $params)
    {
        $sales_order = DB::table('sales_orders')->whereNull("deleted_at")->where("store_id", $params)->get()->pluck("id");
        return $query->whereIn("sales_order_id", $sales_order);
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, "id", "sales_order_id")
            ->with([
                "statusFee",
                "sales_order_detail",
                "dealer",
                "personel",
            ]);
    }

    public function dealer()
    {
        return $this->hasOneThrough(
            Dealer::class,
            SalesOrder::class,
            "id",
            "id",
            "sales_order_id",
            "store_id",
        );
    }

    public function salesOrderOnly()
    {
        return $this->hasOne(SalesOrderV2::class, "id", "sales_order_id");
    }

    public function salesOrderTest()
    {
        return $this->belongsTo(SalesOrderV2::class, "sales_order_id", "id");
    }

    public function getTestAttribute()
    {
        return $this->salesOrderTest()->with("paymentMethod")->first();
    }

    public function getPaymentDueFromAttributeAttribute()
    {
        ini_set('max_execution_time', 1500); //3 minutes

        $payment_method = $this->getTestAttribute();

        $payment_method_days = $payment_method->paymentMethod ? $payment_method->paymentMethod->days : 0;

        $date_delivery_order = $this->dispatchOrder()
            ->orderBy("created_at", "ASC")
            ->with([
                "deliveryOrder",
            ])
            ->whereHas("deliveryOrder")
            ->first();

        $date_due_date = Carbon::now()->format('Y-m-d H:i:s');

        if ($date_delivery_order) {
            $date_due_date = $date_delivery_order ? $date_delivery_order->deliveryOrder->date_delivery : Carbon::now()->format('Y-m-d H:i:s');
        }

        $date_due_date_final = Carbon::createFromFormat('Y-m-d H:i:s', $date_due_date)->addDay($payment_method_days);

        return $date_due_date_final->subDays(7);
    }

    public function getPaymentDueAttribute()
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $payment_method = $this->getTestAttribute();

        $payment_method_days = $payment_method ? ($payment_method->paymentMethod ? $payment_method->paymentMethod->days : 0) : 0;

        $date_delivery_order = $this->dispatchOrder()
            ->orderBy("created_at", "ASC")
            ->with([
                "deliveryOrder",
            ])
            ->whereHas("deliveryOrder")
            ->first();

        $date_due_date = Carbon::now()->format('Y-m-d H:i:s');

        if ($date_delivery_order) {
            $date_due_date = $date_delivery_order ? $date_delivery_order->deliveryOrder->date_delivery : Carbon::now()->format('Y-m-d H:i:s');
        }

        $date_due_date_final = $payment_method_days <= 0 ? Carbon::createFromFormat('Y-m-d H:i:s', $date_due_date) : Carbon::createFromFormat('Y-m-d H:i:s', $date_due_date)->addDays($payment_method_days);

        return $date_due_date_final;
    }

    public function scopePaymentDue($query)
    {
        $dateminus7 = Carbon::now()->subDays(7);

        return $query->whereNull("deleted_at")->whereHas(
            "dispatchOrder",
            function ($QQQ) use ($dateminus7) {
                $QQQ->whereHas("deliveryOrder");
            }
        );
    }

    public function scopeBetweenProformaOrInvoiceDate($query, $params, $params2)
    {
        return $query->where(function ($query) use ($params, $params2) {
            return $query->where(function ($q) use ($params, $params2) {
                $q->whereDate('created_at', '>=', Carbon::parse($params)->format("Y-m-d"))
                    ->whereDate('created_at', "<=", Carbon::parse($params2)->format("Y-m-d"));
            })->orWhereHas('invoiceProforma', function ($q) use ($params, $params2) {
                $q->whereDate('issued_date', '>=', Carbon::parse($params)->format("Y-m-d"))
                    ->whereDate('issued_date', "<=", Carbon::parse($params2)->format("Y-m-d"));
            });
        });
    }

    public function scopePaymentMethod($query, $method)
    {
        return $query->whereHas("salesOrderOnly", function ($sub) use ($method) {
            return $sub->where("payment_method_id", $method);
        });
    }

    public function payment()
    {
        return $this->hasMany(Payment::class, "invoice_id", "id")
            ->orderBy('payment_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    public function lastPayment()
    {
        return $this->hasOne(Payment::class, "invoice_id", "id")->orderBy('payment_date', 'desc');
    }

    public function user()
    {
        return $this->hasOne(User::class, "id", "user_id")->with("personel")->withTrashed();
    }

    public function receipt()
    {
        return $this->hasOne(ProformaReceipt::class, "id", "receipt_id");
    }

    public function confirmedBy()
    {
        return $this->hasOne(Personel::class, "id", "confirmed_by");
    }

    public function redeemedVoucher()
    {
        return $this->hasMany(RedeemedVoucher::class);
    }

    public function invoiceByDealerId($query, $store_id)
    {
        return $query->whereHas("salesOrderOnly", function ($sub) use ($store_id) {
            $sub->where("store_id", $store_id);
        });
    }

    public function scopeInvoiceListPerDealerPerQuartal($query, $quartal, $year, $store_id, $personel_id = null, $dealer_type = null)
    {
        $dealer = Dealer::findOrFail($store_id);
        $date_start = CarbonImmutable::parse($year . "-01-01")->startOfYear();
        $date_end = null;
        $invoices = [];
        if ($dealer_type) {
            $invoices = self::query()
                ->with([
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "dealer" => function ($QQQ) {
                                return $QQQ->with([
                                    "ditributorContract",
                                ]);
                            },
                        ]);
                    },
                ])
                ->invoiceInQuarter($quartal, $year, $store_id, $personel_id)
                ->get()
                ->filter(function ($invoice) use ($dealer_type) {

                    /* check order is inside contract */
                    if ($dealer_type == "distributor") {
                        if ($this->isOrderInsideDistributorContract($invoice)) {
                            return $invoice;
                        }
                    } else if ($dealer_type == "retailer") {
                        if (!$this->isOrderInsideDistributorContract($invoice)) {
                            return $invoice;
                        }
                    } else {
                        return $invoice;
                    }
                })
                ->pluck("id");
        }

        for ($i = 0; $i < 4; $i++) {
            if ($i + 1 == $quartal) {
                $date_end = $date_start->addQuarter($i)->endOfQuarter();
            }
        }

        return $query
            ->invoiceInQuarter($quartal, $year, $store_id, $personel_id)
            ->when($dealer_type, function ($QQQ) use ($invoices) {
                return $QQQ->whereIn("id", $invoices);
            });
    }

    public function scopeInvoiceInQuarter($query, $quartal, $year, $store_id, $personel_id = null)
    {
        return $query
            ->whereYear("created_at", $year)
            ->whereRaw("quarter(invoices.created_at) = " . $quartal)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->considerOrderStatusForRecap();
            })
            ->whereHas("salesOrderOnly", function ($QQQ) use ($year, $store_id, $personel_id) {
                return $QQQ
                    ->where("store_id", $store_id)
                    ->where("type", "1")
                    ->when($personel_id, function ($QQQ) use ($personel_id) {
                        return $QQQ->where("personel_id", $personel_id);
                    });
            });
    }

    public function invoiceProforma()
    {
        return $this->hasOne(InvoiceProforma::class, "invoice_id", "id");
    }

    public function scopeSupervisor($query, $personel = null)
    {
        $personel_id = $personel ?? $this->getPersonel();
        /* get dealer id list */
        $dealers = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $personel_id)->pluck("id")->toArray();

        /* get sub dealer id list */
        $sub_dealers = DB::table('sub_dealers')->whereNull("deleted_at")->whereIn("personel_id", $personel_id)->pluck("id")->toArray();

        $stores = array_unique(array_merge($dealers, $sub_dealers));

        $invoice = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("store_id", $stores)->get()->pluck("id");

        return $query->whereIn("sales_order_id", $invoice);
    }

    public function scopeByPersonel($query, $personel_id)
    {
        /* get dealer id list */
        $dealers = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $personel_id)->pluck("id")->toArray();

        /* get sub dealer id list */
        $sub_dealers = DB::table('sub_dealers')->whereNull("deleted_at")->where("personel_id", $personel_id)->pluck("id")->toArray();
        $stores = array_unique(array_merge($dealers, $sub_dealers));

        $invoice = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("store_id", $stores)->get()->pluck("id");

        return $query
            ->where(function ($QQQ) use ($invoice) {
                return $QQQ
                    ->whereIn("sales_order_id", $invoice)
                    ->whereHas("salesOrderOnly", function ($QQQ) {
                        return $QQQ->whereIn("status", ["confirmed", "returned"]);
                    })
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->whereIn("delivery_status", ["2"])
                            ->orWhere("payment_status", "!=", "settle");
                    });
            })
            ->orWhere(function ($QQQ) use ($invoice, $personel_id) {
                return $QQQ
                    ->whereHas("salesOrderOnly", function ($QQQ) use ($invoice, $personel_id) {
                        return $QQQ
                            ->where("personel_id", $personel_id)
                            ->whereIn("status", ["confirmed", "returned"]);
                    })
                    ->where("payment_status", "settle")
                    ->whereIn("delivery_status", ["1", "3"]);
            });
    }

    public function scopeNotCanceled($query)
    {
        return $query->whereHas("salesOrderOnly", function ($sub) {
            $sub->where("status", "!=", "canceled");
        });
    }

    public function scopeByProformaNumberOrInvoice($query, $parameter)
    {
        return $query->where(function ($QQQ) use ($parameter) {
            return $QQQ
                ->whereHas("invoiceProforma", function ($query) use ($parameter) {
                    return $query->where("invoice_proforma_number", "like", "%" . $parameter . "%");
                })
                // ->where("proforma_number", "like", "%" . $parameter . "%")
                ->orWhere("invoice", "like", "%" . $parameter . "%");
        });
    }

    public function entrusmentPayment()
    {
        return $this->hasMany(EntrusmentPayment::class, "invoice_id", "id");
    }

    public function scopeDetailByMonth($query, $tahun, $bulan, $id, $payment_status = null)
    {
        return $query
            ->whereYear("created_at", $tahun)
            ->when(is_array($payment_status), function ($QQQ) use ($payment_status) {
                if (count($payment_status) > 0) {
                    return $QQQ->whereIn("payment_status", $payment_status);
                }
            })
            ->whereMonth("created_at", $bulan)
            ->whereHas('salesOrder', function ($QQQ) use ($id) {
                return $QQQ->where('personel_id', $id);
            });
    }

    public function getTotalInvoiceAttribute()
    {
        $total_payment = $this->total + $this->ppn;
        return $total_payment;
    }

    public function getNominalAttribute()
    {
        $total_payment = $this->payment()->sum("nominal");
        return $total_payment;
    }

    public function getTotalInvoiceTerbilangAttribute()
    {
        $terbilang = Terbilang::make($this->total + $this->ppn);
        return $terbilang;
    }

    public function getPaymentTimeAttribute()
    {
        if ($this->payment_status == "settle" && in_array($this->salesOrder()->first()?->status, ["returned", "pending", "confirmed"])) {
            $lats_payment = $this->lastPayment()->first() ? $this->lastPayment()->first()->payment_date : $this->created_at->format('Y-m-d');
            $to = $this->created_at->startOfDay();
            $from = Carbon::parse($lats_payment)->endOfDay();
            return $to->diffInDays($from, false) < 0 ? 0 : $to->diffInDays($from, false);
        } else {
            if ($this->created_at) {
                $to = $this->created_at->startOfDay();
                $from = Carbon::now()->endOfDay()->setTimezone('Asia/Jakarta');
                return $to->diffInDays($from, false) < 0 ? 0 : $to->diffInDays($from, false);
            }
            return 0;
        }
    }

    public function getLastPaymentAttribute()
    {
        $last_payment = $this->payment()->orderBy("payment_date", "desc")->first();

        return $last_payment == null ? "-" : $last_payment->payment_date;
    }

    public function getRemainingAttribute()
    {
        $insufficient = $this->getTotalInvoiceAttribute() - $this->getNominalAttribute();
        return $insufficient;
    }

    /**
     * scope region
     *
     * @param [type] $QQQ
     * @param [type] $region_id
     * @return void
     */
    public function scopeByRegion($QQQ, $region_id)
    {
        $marketing_list = $this->marketingListByAreaId($region_id);
        $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
        return $QQQ->whereIn("sales_order_id", $sales_orders);
    }

    public function scopePersonelBranch($query, $personel_id = null)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", ($personel_id ? $personel_id : auth()->user()->personel_id))->pluck("region_id");

        /* get marketing on branch */
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);

        /* get dealer marketing on branch, to get only marketing which handle dealer today */
        $personel_dealer = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $marketing_on_branch)->pluck("personel_id");

        /* get sales order by personel */
        $sales_orders_id = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $personel_dealer)->pluck("id");

        return $query->whereIn("sales_order_id", $sales_orders_id);
    }

    /**
     * filer by owner, dealer_id, dealer name
     *
     * @param [type] $query
     * @return void
     */
    public function scopeByOwnerDealerIdDealerName($query, $parameter)
    {
        $dealers = DealerV2::where("name", "like", "%" . $parameter . "%")
            ->whereNull("deleted_at")
            ->orWhere("dealer_id", "like", "%" . $parameter . "%")
            ->orWhere("owner", "like", "%" . $parameter . "%")
            ->pluck("id")->toArray();

        $sub_dealers = SubDealer::where("name", "like", "%" . $parameter . "%")
            ->orWhere("sub_dealer_id", "like", "%" . $parameter . "%")
            ->orWhere("owner", "like", "%" . $parameter . "%")
            ->pluck("id")->toArray();

        $stores = array_unique(array_merge($dealers, $sub_dealers));

        $sales_orders = DB::table('sales_orders')->whereIn("store_id", $stores)->pluck("id")->toArray();
        return $query
            ->whereIn("sales_order_id", $sales_orders);
    }

    /**
     * scope by dealer name
     *
     * @param [type] $query
     * @param [type] $dealer_name
     * @return void
     */
    public function scopeByDealerName($query, $dealer_name)
    {
        return $query->whereHas("salesOrderOnly", function ($QQQ) use ($dealer_name) {
            return $QQQ->whereHas("dealerV2", function ($QQQ) use ($dealer_name) {
                return $QQQ->where("name", "like", "%" . $dealer_name . "%");
            });
        });
    }

    public function scopeByDealerNameAndbyPersonel($query, $name)
    {
        return $query
            ->whereHas("salesOrderOnly", function ($QQQ) use ($name) {
                return $QQQ->where(function ($QQQ) use ($name) {
                    return $QQQ->whereHas("dealerV2", function ($QQQ) use ($name) {
                        return $QQQ->where("name", "like", "%" . $name . "%");
                    });
                })->orWhere(function ($query) use ($name) {
                    return $query->whereHas("personel", function ($query) use ($name) {
                        return $query->where("name", "like", "%" . $name . "%");
                    });
                });
            });
    }

    /**
     * scope by dealer name
     *
     * @param [type] $query
     * @param [type] $dealer_name
     * @return void
     */
    public function scopeByPersonelName($query, $dealer_name)
    {
        return $query->whereHas("salesOrderOnly", function ($QQQ) use ($dealer_name) {
            return $QQQ->whereHas("personel", function ($QQQ) use ($dealer_name) {
                return $QQQ->where("name", "like", "%" . $dealer_name . "%");
            });
        });
    }

    public function dispatchOrder()
    {
        return $this->hasMany(DispatchOrder::class, "invoice_id", "id")
            ->orderBy("created_at", 'ASC')
            ->with("deliveryOrder");
    }

    public function dispatchOrderTest()
    {
        return $this->hasMany(DispatchOrder::class, "invoice_id", "id")
            ->orderBy("date_delivery", 'ASC')
            ->with("deliveryOrder", function ($QQQ) {
                return $QQQ
                    ->orderBy("date_delivery", 'ASC');
            });
    }

    public function scopeDispatchOrderhasNotDeliveryOrder($query)
    {
        return $query->whereHas("dispatchOrder", function ($QQQ) {
            return $QQQ->whereDoesntHave("deliveryOrder", function ($QQQ) {
                return $QQQ->where("status", "!=", "canceled");
            });
        });
    }

    /**
     * invoice has delivery order
     *
     * @param [type] $query
     * @param [type] $status
     * @return void
     */
    public function scopeHasDeliveryOrder($query, $status = null)
    {
        return $query->whereHas("dispatchOrder", function ($QQQ) use ($status) {
            return $QQQ->whereHas("deliveryOrder", function ($QQQ) use ($status) {
                return $QQQ
                    ->when($status, function ($QQQ) use ($status) {
                        return $QQQ
                            ->when(is_array($status), function ($QQQ) use ($status) {
                                return $QQQ->whereIn("status", $status);
                            })
                            ->when(!is_array($status), function ($QQQ) use ($status) {
                                return $QQQ->where("status", $status);
                            });
                    });
            });
        });
    }

    /**
     * invoice has receiving good order
     *
     * @param [type] $query
     * @param [type] $status
     * @return void
     */
    public function scopeHasReceivingGood($query, $receiving_good_status = "yes", $date_received_start = null, $date_received_end = null)
    {
        return $query->whereHas("dispatchOrder", function ($QQQ) use ($receiving_good_status, $date_received_start, $date_received_end) {
            return $QQQ
                ->whereHas("deliveryOrder", function ($QQQ) use ($receiving_good_status, $date_received_start, $date_received_end) {
                    return $QQQ
                        ->where("status", "send")

                        /* filter receiving status */
                        ->when($receiving_good_status && !is_null($date_received_start) && !is_null($date_received_end), function ($QQQ) use ($receiving_good_status, $date_received_start, $date_received_end) {
                            return $QQQ
                                ->hasReceivingGoods($receiving_good_status, $date_received_start, $date_received_end);
                        })

                        /* filter receiving status */
                        ->when($receiving_good_status, function ($QQQ) use ($receiving_good_status) {
                            return $QQQ
                                ->hasReceivingGoods($receiving_good_status);
                        });
                });
        });
    }

    public function scopeGoodsReceiptHistory($query, $receiving_good_status = "yes", $date_received_start = null, $date_received_end = null)
    {
        return $query->where(function ($query) use ($receiving_good_status, $date_received_start, $date_received_end) {
            $query->whereHas("dispatchOrder", function ($QQQ) use ($receiving_good_status, $date_received_start, $date_received_end) {
                return $QQQ
                    ->whereHas("deliveryOrder", function ($QQQ) use ($receiving_good_status, $date_received_start, $date_received_end) {
                        return $QQQ
                            ->where("status", "send")

                            /* filter receiving status */
                            ->when($receiving_good_status && !is_null($date_received_start) && !is_null($date_received_end), function ($QQQ) use ($receiving_good_status, $date_received_start, $date_received_end) {
                                return $QQQ
                                    ->hasReceivingGoods($receiving_good_status, $date_received_start, $date_received_end);
                            })

                            /* filter receiving status */
                            ->when($receiving_good_status, function ($QQQ) use ($receiving_good_status) {
                                return $QQQ
                                    ->hasReceivingGoods($receiving_good_status);
                            });
                    });
            })->orWhere('delivery_status', 3);
        });
    }

    public function scopeByMarketing($query, $marketing = null)
    {
        return $query
            ->whereHas("deliveryOrder", function ($QQQ) use ($marketing) {
                return $QQQ
                    ->whereHas("marketing", function ($QQQ) use ($marketing) {
                        return $QQQ->where("name", "like", "%" . $marketing . "%");
                    });
            });
    }

    public function scopeByReceivedBy($query, $marketing)
    {
        return $query
            ->whereHas("deliveryOrder", function ($QQQ) use ($marketing) {
                return $QQQ
                    ->whereHas("receivingGoods", function ($QQQ) use ($marketing) {
                        return $QQQ->whereHas("receivedBy", function ($QQQ) use ($marketing) {
                            return $QQQ->where("name", "like", "%" . $marketing . "%");
                        });
                    });
            });
    }

    public function scopeByDealer($query, $dealer = null)
    {
        return $query
            ->whereHas("deliveryOrder", function ($QQQ) use ($dealer) {
                return $QQQ
                    ->whereHas("dealer", function ($QQQ) use ($dealer) {
                        return $QQQ->where("name", "like", "%" . $dealer . "%")
                            ->orWhere("dealer_id", "like", "%" . $dealer . "%");
                    });
            });
    }

    public function dispatchOrderFirst()
    {
        return $this->hasMany(DispatchOrder::class, "invoice_id", "id")->with("deliveryOrder");
    }

    public function dispatchOrderDetail()
    {
        return $this->hasManyThrough(
            DispatchOrderDetail::class,
            DispatchOrder::class,
            "invoice_id",
            "id_dispatch_order",
            "id",
            "id"
        );
    }

    public function getStatusAttribute()
    {
        if ($this->delivery_status == 1) {
            return "done";
        } elseif ($this->dispatchOrder()->count() > 0 && $this->delivery_status == 2) {
            return "issued";
        } else {
            return "planned";
        }
    }

    public function scopeFilterDispatchStatus($query, array $delivery_statuses)
    {
        return $query->where(function($q) use($delivery_statuses){

            if(in_array("done", $delivery_statuses)){
                return $q->where('delivery_status', 1);
            }

            if(in_array("issued", $delivery_statuses)){
                return $q->orWhere(function($q){
                    $q->where('delivery_status', 2)->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('discpatch_order')
                              ->whereColumn('discpatch_order.id', 'invoices.sales_order_id')
                              ->whereNull('discpatch_order.deleted_at');
                    });
                });
            }

            if(in_array("planned", $delivery_statuses)){
                return $q->orWhere(function($q){
                    $q->where('delivery_status', [1,2])->orWhereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('discpatch_order')
                              ->whereColumn('discpatch_order.id', 'invoices.sales_order_id')
                              ->whereNull('discpatch_order.deleted_at');
                    });
                });
            }
        });
    }

    public function scopeDeliveryStatus($query, array $delivery_statuses)
    {
        return $query
            ->when(in_array("done", $delivery_statuses) && count($delivery_statuses) == 1, function ($QQQ) {
                return $QQQ->deliveryStatusDone();
            })
            ->when(in_array("issued", $delivery_statuses) && count($delivery_statuses) == 1, function ($QQQ) {
                return $QQQ->deliveryStatusIssued();
            })
            ->when(in_array("planned", $delivery_statuses) && count($delivery_statuses) == 1, function ($QQQ) {
                return $QQQ->deliveryStatusPlanned();
            })
            ->when(in_array("done", $delivery_statuses) && in_array("issued", $delivery_statuses) && count($delivery_statuses) == 2, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusDone()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusIssued();
                    });
            })
            ->when(in_array("done", $delivery_statuses) && in_array("planned", $delivery_statuses) && count($delivery_statuses) == 2, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusDone()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusPlanned();
                    });
            })
            ->when(in_array("issued", $delivery_statuses) && in_array("planned", $delivery_statuses) && count($delivery_statuses) == 2, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusIssued()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusPlanned();
                    });
            })
            ->when(in_array("done", $delivery_statuses) && in_array("issued", $delivery_statuses) && in_array("planned", $delivery_statuses) && collect($delivery_statuses)->unique()->count() == 3, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusDone()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusIssued();
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusPlanned();
                    });
            });
    }

    public function scopeDeliveryStatusDone($query)
    {
        return $query->whereIn("delivery_status", [1, 3]);
    }

    public function scopeDeliveryStatusIssued($query)
    {
        return $query
            ->where(function ($QQQ) {
                return $QQQ
                    ->where("date_delivery", "<=", Carbon::now()->format('Y-m-d'))
                    ->orWhereNull("date_delivery");
            })
            ->whereNotIn("delivery_status", [1, 3]);
    }

    public function scopeDeliveryStatusPlanned($query)
    {
        return $query->whereNotIn("delivery_status", [1, 3])->where("date_delivery", ">", Carbon::now()->format('Y-m-d'));
    }

    public function scopeByDateBetween($query, $start_date, $end_date)
    {
        return $query
            ->whereDate('created_at', '>=', $start_date)
            ->whereDate('created_at', '<=', $end_date);
    }

    /**
     * list proforma whicy not received all
     */
    public function scopeNotReceivedAll($query)
    {
        // $invoices = Invoice::query()
        //     ->whereHas("salesOrder", function ($QQQ) {
        //         return $QQQ
        //             ->whereHas("sales_order_detail");
        //     })
        //     ->whereHas("dispatchOrder")
        //     ->with([
        //         "salesOrder" => function ($QQQ) {
        //             return $QQQ->with([
        //                 "sales_order_detail",
        //             ]);
        //         },
        //         "dispatchOrder" => function ($QQQ) {
        //             return $QQQ->with([
        //                 "dispatchOrderDetail",
        //             ]);
        //         },
        //     ])
        //     ->get();

        // /* get all invoices id that have not been received all */
        // $invoices_has_received_all = [];

        // foreach ($invoices as $key => $value) {

        //     /* get sum of unit purchased */
        //     $quantity_purchased = collect($value->salesOrder->sales_order_detail)->sum("quantity");

        //     /* get unit received */
        //     $quantity_received = 0;

        //     foreach ($value->dispatchOrder as $dispatch) {
        //         $quantity_received += collect($dispatch->dispatchOrderDetail)->sum("quantity_unit");
        //     }

        //     /* compare unit purchased with unit received */
        //     if ($quantity_received >= $quantity_purchased) {
        //         array_push($invoices_has_received_all, $value->id);
        //     }
        // }

        return $query
            ->whereNotIn("delivery_status", ["1", "3"])
            ->whereDate("created_at", ">=", now()->subMonths(3)->startOfDay()->format("Y-m-d"));
    }

    /**
     * delivery order of invoice
     */
    public function deliveryOrder($status = "send")
    {
        return $this
            ->hasManyThrough(
                DeliveryOrder::class,
                DispatchOrder::class,
                "invoice_id",
                "dispatch_order_id",
                "id",
                "id"
            )
            ->when($status, function ($QQQ) use ($status) {
                return $QQQ
                    ->where("delivery_orders.status", $status)
                    ->orderBy("delivery_orders.date_delivery");
            });
    }

    /**
     * delivery order of invoice
     */
    public function firstDeliveryOrder($status = "send")
    {
        return $this
            ->hasOneThrough(
                DeliveryOrder::class,
                DispatchOrder::class,
                "invoice_id",
                "dispatch_order_id",
                "id",
                "id"
            )
            ->when($status, function ($QQQ) use ($status) {
                return $QQQ
                    ->where("delivery_orders.status", $status)
                    ->orderBy("delivery_orders.date_delivery");
            });
    }

    public function deliveryOrders($status = "send")
    {
        return $this
            ->hasManyThrough(
                DeliveryOrder::class,
                DispatchOrder::class,
                "invoice_id",
                "dispatch_order_id",
                "id",
                "id"
            )
            ->where("delivery_orders.status", $status)
            ->orderBy("delivery_orders.date_delivery");
    }

    public function getFirstDeliveryOrderDateAttribute()
    {
        return $this->firstDeliveryOrder()->first() ? $this->firstDeliveryOrder()->first()->date_delivery : $this->created_at;
    }

    /**
     * receiving good list has received
     *
     * @return boolean
     */
    public function hasReceived()
    {
        return $this->hasManyDeepFromRelations($this->deliveryOrder(), (new DeliveryOrder())->receivingGoodHasReceived());
    }

    /**
     * receiving good detail list has received
     *
     * @return boolean
     */
    public function allProductHasReceived()
    {
        return $this->hasManyDeepFromRelations($this->hasReceived(), (new ReceivingGood())->receivingGoodDetail());
    }

    /**
     * receiving good detail list has received
     *
     * @return boolean
     */
    public function goodProductHasReceived()
    {
        return $this->hasManyDeepFromRelations($this->hasReceived(), (new ReceivingGood())->receivingGoodDetail())->where("receiving_good_details.status", "delivered");
    }

    /**
     * delivery order of invoice
     */
    public function deliveryOrderProforma()
    {
        return $this
            ->hasManyThrough(
                DeliveryOrder::class,
                DispatchOrder::class,
                "invoice_id",
                "dispatch_order_id",
                "id",
                "id"
            );
    }

    /**
     * last receiving good
     */
    public function lastReceivingGood()
    {
        return $this->hasOneDeepFromRelations($this->deliveryOrderProforma(), (new DeliveryOrder)->receivingGoodHasReceived())
            ->where("delivery_orders.status", "send")
            ->where("receiving_goods.delivery_status", "2")
            ->orderBy("receiving_goods.date_received", "desc");
    }

    public function scopeByDeliveryOrderNumber($query, $delivery_order_number)
    {
        return $query->whereHas("deliveryOrder", function ($QQQ) use ($delivery_order_number) {
            return $QQQ->where("delivery_order_number", "like", "%" . $delivery_order_number . "%");
        });
    }

    public function allPayment()
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'id');
    }

    public function salesOrderDetail()
    {
        return $this->hasManyThrough(
            SalesOrderDetail::class,
            SalesOrder::class,
        );
    }

    public function creditMemos()
    {
        return $this->hasMany(CreditMemo::class, "origin_id", "id")
            ->validCreditMemo();
    }

    public function creditMemoDestination()
    {
        return $this->hasMany(CreditMemo::class, "destination_id", "id")
            ->validCreditMemo();
    }

    public function allCreditMemoDestination()
    {
        return $this->hasMany(CreditMemo::class, "destination_id", "id");
    }

    public function allCreditMemoOrigins()
    {
        return $this->hasMany(CreditMemo::class, "origin_id", "id");
    }

    public function scopeNoProformaIdDealerDealerName($query, $parameter)
    {
        return $query->where(function ($QQQ) use ($parameter) {
            return $QQQ
                ->whereHas("salesOrder", function ($QQQ) use ($parameter) {
                    return $QQQ
                        ->whereHas("dealer", function ($QQQ) use ($parameter) {
                            return $QQQ
                                ->where("dealer_id", $parameter)
                                ->orWhere("name", "like", "%" . $parameter . "%");
                        });
                })
                ->orWhere("invoice", "like", "%" . $parameter . "%");
        });
    }

    public function scopeByStoreId($query, $store_id)
    {
        return $query->whereHas("salesOrder", function ($QQQ) use ($store_id) {
            return $QQQ
                ->where("store_id", $store_id)
                ->where("type", "1")
                ->where("model", "1");
        });
    }
}
