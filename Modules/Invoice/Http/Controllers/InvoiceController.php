<?php

namespace Modules\Invoice\Http\Controllers;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use App\Traits\DistributorTrait;
use App\Traits\ResponseHandler;
use App\Traits\SuperVisorCheckV2;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\DealerBenefit;
use Modules\DataAcuan\Entities\PaymentDayColor;
use Modules\DataAcuan\Entities\Product;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Events\HandOverSalesOrderEvent;
use Modules\Invoice\Http\Requests\InvoiceRequest;
use Modules\Invoice\Jobs\InvoiceNotificationJob;
use Modules\Invoice\Transformers\InvoiceCollectionResource;
use Modules\Invoice\Transformers\InvoiceResource;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\DirectSalesOrderExport;
use Modules\SalesOrder\Actions\Order\ConfirmOrderAction;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class InvoiceController extends Controller
{
    use DisableAuthorization;
    use SuperVisorCheckV2;
    use DistributorTrait;
    use DistributorStock;
    use ResponseHandler;
    use ChildrenList;

    protected $model = Invoice::class;
    protected $request = InvoiceRequest::class;
    protected $resource = InvoiceResource::class;
    protected $collectionResource = InvoiceCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [
            "salesOrder",
            "salesOrder.confirmedBy.name",
            "salesOrder.sales_order_detail",
            "salesOrder.sales_order_detail.product",
            "salesOrder.sales_order_detail.product.package",
            "salesOrder.dealer.agencyLevel",
            "salesOrder.dealer.adress_detail.province",
            "salesOrder.dealer.adress_detail.city",
            "salesOrder.dealer.adress_detail.district",
            "salesOrder.personel",
            "salesOrder.personel.position",
            "salesOrder.personel.supervisor",
            "salesOrder.paymentMethod",
            "payment",
            "user.personel",
            "user.personel.position",
            "user",
            "invoiceProforma",
            "payment_time",
        ];
    }

    public function includes(): array
    {
        return [
            "receipt",
            "confirmedBy",

            "dispatchOrder",
            "dispatchOrderTest",
            "firstDeliveryOrder",
            "dispatchOrder.deliveryOrder",
            "dispatchOrder.dispatchOrderDetail",
            "dispatchOrder.deliveryOrder.receivingGoods",

            "invoiceProforma",
            "entrusmentPayment",

            "receipt.confirmedBy",

            "salesOrder.dealer",
            "salesOrder.confirmedBy",
            "salesOrder.salesCounter",
            "salesOrder.paymentMethod",
            "salesOrder.salesCounter.position",

            "salesOrder.returnedBy",
            "salesOrder.returnedBy.position",
            "salesOrder.statusFee",

            "salesOrder.dealer.personel",
            "salesOrder.dealer.personel.position",

            "receipt.confirmedBy.position",
            "dispatchOrderTest.deliveryOrder",
            "payment_time",

            "creditMemos",
            "creditMemos.destination",
            "creditMemos.creditMemoDetail",
            "creditMemoDestination.origin",
            "creditMemoDestination.destination",

            "allCreditMemoDestination.origin",
            "allCreditMemoDestination.destination",

            "payment",
            "payment.reporter",
            "payment.reporter.position",
        ];
    }

    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
            'byRegion',
            "byStoreId",
            'byPersonel',
            "byDealerName",
            "byDateBetween",
            "byDeliveryOrderNumber",
            "byDealerNameAndbyPersonel",
            "byOwnerDealerIdDealerName",
            "byProformaNumberOrInvoice",
            'deliveryStatus',
            'detailByMonth',
            'invoiceListPerDealerPerQuartal',
            'invoicePerDealer',
            'personelBranch',
            'supervisor',
            "dispatchOrderhasNotDeliveryOrder",
            "hasDeliveryOrder",
            "hasReceivingGood",
            "noProformaIdDealerDealerName",
            "notCanceled",
            "notReceivedAll",
            "paymentDue",
            "paymentMethod",
            "betweenProformaOrInvoiceDate",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'user_id',
            'invoice',
            'created_at',
            'updated_at',
            'date_delivery',
            'sales_order_id',
            'payment_status',
            'delivery_status',
            "salesOrder.return",
            "salesOrder.return",
            "salesOrder.store_id",
            "salesOrder.counter_id",
            "salesOrder.personel_id",
            "salesOrder.returned_by",
            "salesOrder.order_number",
            "invoiceProforma.invoice_proforma_number",
        ];
    }
    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'user_id',
            'sales_order_id',
            'invoice',
            'total',
            'payment_status',
            'date_delivery',
            'created_at',
            'updated_at',
            "salesOrder.personel_id",
            "lastPayment.payment_date",
            "payment_time",
            "payment.payment_date",
            "salesOrder.proforma",
            "salesOrder.order_number",
            "invoiceProforma.invoice_proforma_number",
        ];
    }

    public function aggregates(): array
    {
        return [
            "creditMemos.total",
            "creditMemoDestination.total",
        ];
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
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        $query->select('*');
        return $query->whereHas("salesOrder", function ($QQQ) {
            return $QQQ->whereHas("sales_order_detail");
        });
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        if ($request->disabled_pagination) {
            return $query
                ->when($request->has("limit"), function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })
                ->orderBy('created_at', 'DESC')
                ->get();
        } else {
            $data_invoice = $query
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
                ->when($request->has("paymentDue"), function ($QQQ) use ($request) {
                    return $QQQ->where("payment_status", "!=", "settle");
                })
                ->when($request->has('dispatch_status'), function ($q) use ($request) {
                    return $q->deliveryStatus($request->dispatch_status);
                })

                /* customize sort */
                ->when($request->has('sorting_column'), function ($q) use ($request) {
                    switch ($request->sorting_column) {
                        case 'payment_time':
                            $from = Carbon::now()->startOfDay()->format('Y-m-d');
                            return $q
                                ->addSelect(DB::raw("CASE WHEN payment_status = 'settle' AND (select status from sales_orders where id = invoices.sales_order_id and sales_orders.deleted_at is null) IN ('confirmed', 'pending', 'returned') THEN CASE WHEN (select payment_date from payments where invoice_id = invoices.id and payments.deleted_at is null order by payment_date desc limit 1) != null THEN DATEDIFF((select payment_date from payments where invoice_id = invoices.id and payments.deleted_at is null order by payment_date desc limit 1), created_at) ELSE DATEDIFF((select payment_date from payments where invoice_id = invoices.id and payments.deleted_at is null order by payment_date desc limit 1), date(created_at)) END ELSE DATEDIFF('$from', created_at) END AS diff_in_days"))
                                ->orderBy('diff_in_days', $request->order_type ?? "asc");
                            break;

                        case 'last_payment':
                            return $q
                                ->addSelect(DB::raw('(select payment_date from payments where invoice_id = invoices.id and payments.deleted_at is null order by payment_date desc limit 1) as payment_last'))
                                ->orderBy('payment_last', $request->order_type ?? "asc");
                            break;
                        case 'dealer':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderBy(
                                    DB::table('sales_orders as s')
                                        ->selectRaw("d.name")
                                        ->whereNull("d.deleted_at")
                                        ->whereNull("s.deleted_at")
                                        ->leftJoin('dealers as d', function ($join) {
                                            $join->on('s.store_id', '=', 'd.id')
                                                ->whereNull('d.deleted_at');
                                        })
                                        ->whereColumn('invoices.sales_order_id', 's.id'),
                                    $direction
                                );
                            break;
                        case 'cust_id':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderBy(
                                    DB::table('sales_orders as s')
                                        ->selectRaw("d.dealer_id")
                                        ->whereNull("s.deleted_at")
                                        ->join("dealers as d", "d.id", "s.store_id")
                                        ->whereColumn("invoices.sales_order_id", "s.id"),
                                    $direction
                                );
                            break;
                        case 'total_invoice':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderByRaw("total+ppn {$direction}
                                ");
                            break;
                        case 'remaining':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderBy(
                                    DB::table('invoices as i')
                                        ->selectRaw("(i.total + i.ppn) - if(sum(p.nominal) > 0, sum(p.nominal), 0)")
                                        ->whereNull("p.deleted_at")
                                        ->whereNull("i.deleted_at")
                                        ->leftJoin('payments as p', function ($join) {
                                            $join->on('p.invoice_id', '=', 'i.id')
                                                ->whereNull('p.deleted_at');
                                        })
                                        ->whereColumn("invoices.id", "i.id")
                                        ->groupBy("p.invoice_id")
                                        ->limit(1),
                                    $direction
                                );
                            break;
                        case 'marketing':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderBy(
                                    DB::table('sales_orders as s')
                                        ->selectRaw("CASE
                                            WHEN `s`.`is_office` = '1' THEN 'kantor'
                                            ELSE `p`.`name`
                                        END AS `name`")
                                        ->whereNull("p.deleted_at")
                                        ->whereNull("s.deleted_at")
                                        ->leftJoin('personels as p', function ($join) {
                                            $join->on('p.id', '=', 's.personel_id')
                                                ->whereNull('p.deleted_at');
                                        })
                                        ->whereColumn('invoices.sales_order_id', 's.id'),
                                    $direction
                                );
                            break;

                        case 'order_number':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderBy(
                                    DB::table('sales_orders as s')
                                        ->selectRaw("s.order_number")
                                        ->whereNull("s.deleted_at")
                                        ->whereColumn('invoices.sales_order_id', 's.id'),
                                    $direction
                                );
                            break;

                        case 'invoice_proforma_number':
                            $direction = $request->order_type ?? "asc";
                            return $q
                                ->orderBy(
                                    DB::table('invoice_proformas as ipn')
                                        ->selectRaw("ipn.invoice_proforma_number")
                                        ->whereNull("ipn.deleted_at")
                                    // ->leftJoin('invoice_proformas as ipn', function ($join) {
                                    //     $join->on('ipn.invoice_id', '=', 'i.id')
                                    //         ->whereNull('ipn.deleted_at');
                                    // })
                                        ->whereColumn('invoices.id', 'ipn.invoice_id')
                                        ->groupBy("ipn.invoice_id"),
                                    $direction
                                );
                            break;
                    }
                })

                /* default sort */
                ->when(!$request->has('sorting_column'), function ($q) use ($request) {
                    return $q->orderBy('invoices.created_at', 'desc');
                });

            $paymentDayColor = PaymentDayColor::select("id", "min_days", "max_days", "bg_color", "text_color")->get();
            $data = $data_invoice->paginate($request->limit ? $request->limit : 10)->through(function ($data) use ($paymentDayColor) {
                if (count($paymentDayColor) === 0) {
                    $data->bg_color = "FFFFFF";
                    $data->text_color = "000000";
                    return $data;
                }

                foreach ($paymentDayColor as $color) {
                    if ($color->max_days && $data->payment_time >= $color->min_days && $data->payment_time <= $color->max_days) {
                        $data->bg_color = $color->bg_color;
                        $data->text_color = $color->text_color;
                        return $data;
                    } elseif (!$color->max_days && $data->payment_time >= $color->min_days) {
                        $data->bg_color = $color->bg_color;
                        $data->text_color = $color->text_color;
                        return $data;
                    }
                }

                // Jika tidak ada korespondensi warna yang ditemukan
                $data->bg_color = "FFFFFF";
                $data->text_color = "000000";
                return $data;
            });

            if ($request->order_type_direct) {
                $direct_distributor = $data
                    ->filter(function ($order) use ($request) {
                        if ($this->isOrderInsideDistributorContract($order) && in_array(1, $request->order_type_direct)) {
                            return $order;
                        }
                    });

                /* direct to retailer */
                $direct_retailer = $data
                    ->filter(function ($order) use ($request) {
                        if (!$this->isOrderInsideDistributorContract($order) && in_array(2, $request->order_type_direct)) {
                            return $order;
                        }
                    });

                $data = $direct_distributor->concat($direct_retailer);
            }

            if ($request->paymentDue) {
                foreach ($data as $value) {
                    $payment_method_days = $value->salesOrder->paymentMethod ? $value->salesOrder->paymentMethod->days : 0;
                    $collect_date_delivery_order = collect($value->dispatchOrder)->whereNotNull("deliveryOrder")->sortBy("date_delivery")->first();
                    $date_delivery = 0;
                    $payment_due_date = now();

                    if ($collect_date_delivery_order) {
                        $payment_due_date = Carbon::parse($collect_date_delivery_order->deliveryOrder->date_delivery)->addDays($payment_method_days)->format('Y-m-d H:i:s');
                    } else {
                        $payment_due_date = $value->created_at->addDays($payment_method_days)->format('Y-m-d H:i:s');
                    }

                    $due_date2 = $payment_due_date;

                    $value['payment_method_days'] = $payment_method_days;
                    $value['payment_due_date'] = $payment_due_date;
                    $value['diff_days'] = Carbon::parse($due_date2)->startOfDay()->diffInDays(Carbon::now()->startOfDay()->format('Y-m-d H:i:s'), false);
                }

                if ($request->payment_due_date) {
                    $data = $data->filter(function ($item) use ($request) {
                        return $item->payment_due->isSameDay(Carbon::createFromFormat('Y-m-d', $request->payment_due_date));
                    })->sortBy("payment_due_from_attribute")->values();
                } else {
                    if ($request->limit) {
                        $data = $data->where("payment_due", ">=", Carbon::now()->format('Y-m-d'))->where("payment_due", "<=", Carbon::now()->addDays(8)->format('Y-m-d'))->sortBy("payment_due")->values();
                    } else {
                        $data = $data->where("payment_due_date", "<=", Carbon::now()->addDays(8)->format('Y-m-d'))->sortBy("payment_due_date")->values();
                    }
                }
            }

            return $data;
        }
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $user_id = $request->has("user_id") ? $request->user_id : auth()->id();
        $proforma_number = DB::table('invoices')
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereNull("deleted_at")
                    ->orWhere(function ($QQQ) {
                        return $QQQ
                            ->whereNotNull("deleted_at")
                            ->whereNotNull("canceled_at");
                    });
            })
            ->whereYear("created_at", now())
            ->orderBy("proforma_number", "desc")
            ->orderBy("created_at", "desc")
            ->first();

        $months = [
            "01" => "I",
            "02" => "II",
            "03" => "III",
            "04" => "IV",
            "05" => "V",
            "06" => "VI",
            "07" => "VII",
            "08" => "VIII",
            "09" => "IX",
            "10" => "X",
            "11" => "XI",
            "12" => "XII",
        ];

        /* generate prforma number */
        $proforma = Carbon::now()->format("Y") . "/PP-" . $months[Carbon::now()->format("m")] . "/" . str_pad(($proforma_number ? $proforma_number->proforma_number : 0) + 1, 5, 0, STR_PAD_LEFT);

        /* get receipt for proforma */
        $receipt_proforma = DB::table('proforma_receipts')->whereNull("deleted_at")->where("receipt_for", "1")->orderBy("created_at", "desc")->first();

        /* get current operational manager */
        $operational_manager = Personel::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->where("name", "Operational Manager");
            })
            ->first();

        $attributes["payment_status"] = "unpaid";
        $attributes["confirmed_by"] = $operational_manager ? $operational_manager->id : null;
        $attributes["receipt_id"] = $receipt_proforma ? $receipt_proforma->id : null;

        /* pending */
        if ($request->has("invoice")) {
            // $proforma = $request->invoice;
        }

        if ($request->has("date_delivery")) {
            $attributes["date_delivery"] = $request->date_delivery;
        } else {
            $attributes["date_delivery"] = Carbon::now()->addDays(2);
        }

        $attributes["invoice"] = $proforma;
        $attributes["user_id"] = $user_id;
        $attributes["proforma_number"] = ($proforma_number ? $proforma_number->proforma_number : 0) + 1;
        $entity->fill($attributes);
        $entity->save();
    }

    public function beforeStore(Request $request, $model)
    {
        SalesOrder::findOrFail($request->sales_order_id);
        if ($request->has("user_id")) {
            User::findOrFail($request->user_id);
        }
    }

    public function afterStore(Request $request, $model)
    {
        /**
         * update marketing in confirmed order
         * with marketing in dealer
         */
        $model->salesOrder->status = "confirmed";
        $model->salesOrder->confirmed_id = Auth::user()->personel_id;
        $model->salesOrder->personel_id = $model->salesOrder->dealer?->personel_id;
        $model->salesOrder->is_marketing_freeze = $model->salesOrder?->personel?->status == "2" ? true : false;
        $model->salesOrder->saveQuietly();

        /**
         * generate origin if store is distributor active
         * on confirm action
         */

        /*
        |-------------------------------------------------
        | FEE MARKETING COUNTER
        |-----------------------------------------
        |
         */
        $confirm_order = new ConfirmOrderAction;
        $confirm_order($model->salesOrder);

        /* hand over check to update status fee */
        HandOverSalesOrderEvent::dispatch($model);
    }

    public function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildShowFetchQuery($request, $requestedRelations);
        return $query;
    }

    public function runShowFetchQuery(Request $request, Builder $query, $key): Model
    {
        $direction = $request->has("direction") ? $request->direction : "desc";
        return $query
            ->with([
                "salesOrder" => function ($QQQ) use ($request) {
                    return $QQQ->with([
                        "sales_order_detail" => function ($QQQ) use ($request) {
                            return $QQQ->withAggregate("product", "name")

                            // sorting by product_name in sales_order_detail
                                ->when($request->sorting_column == "product_name", function ($query) use ($request) {
                                    $sort_type = "asc";
                                    if ($request->has("direction")) {
                                        $sort_type = $request->direction;
                                    }
                                    return $query->orderBy("product_name", $sort_type);
                                });
                        },
                    ]);
                },
            ])
            ->with(["payment" => function ($query) use ($direction) {
                return $query->orderBy("payment_date", $direction);
            }])
            ->findOrFail($key);
    }

    public function beforeUpdate(Request $request, $model)
    {
        $request->merge([
            "canceled_at" => null,
        ]);

        /**
         * new rule accroding 2024-01-10
         * proforma can not be updated if there
         * 1. active dispatch order
         * 2. invoice proforma
         */
        $is_updatable = true;
        if ($model?->dispatchOrder) {
            if ($model->dispatchOrder) {
                $is_updatable = $model->dispatchOrder
                    ->filter(fn($dispatch) => $dispatch->is_active == true)
                    ->count() == 0;
            }
        }
        if ($model?->invoiceProforma) {
            if ($model->invoiceProforma) {
                $is_updatable = false;
            }
        }

        if (!$is_updatable) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Can not update proforma that has active dispatch order or invoice",

                    /**
                     * PENDING
                     * "Can not delete direct sales that has invoice proforma or already sent or has receving good",
                     */
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    public function beforeDestroy(Request $request, $model)
    {

        /**
         * -------------------------------------------
         * PENDING
         * ---------------------------------------
         * proforma can not be deleted if there
         * 1. invoice proforma
         * 2. or has receving good
         * 3. or last delivery order is send
         *
         *
         *
         * $is_deletable = true;
         * if ($model?->invoiceProforma) {
         *     if ($model->invoiceProforma) {
         *         $is_deletable = false;
         *     }
         * } elseif ($model?->dispatchOrder) {

         *     if ($model->dispatchOrder) {
         *         $delivery_order = collect($model?->dispatchOrder)
         *             ->filter(fn($dispatch) => $dispatch->deliveryOrder)
         *             ->sortBy(function ($dispatch) {
         *                 return $dispatch->deliveryOrder->date_delivery;
         *             })
         *             ->filter(function ($dispatch) {
         *                 return Carbon::parse($dispatch->deliveryOrder->date_delivery)->format("Y-m-d") <= now()->format("Y-m-d");
         *             })
         *             ->first();

         *         if ($delivery_order) {
         *             $is_deletable = false;
         *         } elseif ($model?->hasReceived->count() > 0) {
         *             $is_deletable = false;
         *         }
         *     }
         * }
         */

        /**
         * new rule accroding 2024-01-10
         * proforma can not be deleted if there
         * 1. active dispatch order
         * 2. invoice proforma
         */
        if ($model?->dispatchOrder) {
            if ($model->dispatchOrder) {
                $is_deletable = $model->dispatchOrder
                    ->filter(fn($dispatch) => $dispatch->is_active == true)
                    ->count() == 0;
            }
        }
        if ($model?->invoiceProforma) {
            if ($model->invoiceProforma) {
                $is_deletable = false;
            }
        }

        if (!$is_deletable) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Can not update proforma that has active dispatch order or invoice",

                    /**
                     * PENDING
                     * "Can not delete direct sales that has invoice proforma or already sent or has receving good",
                     */
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    public function afterDestroy(Request $request, $model)
    {
        SalesOrder::query()
            ->where("id", $model->sales_order_id)
            ->update([
                "status" => "submited",
            ]);
    }

    public function proformaNumber($proforma)
    {
        $proforma += 1;
        $length = strlen((string) $proforma);
        $nol = "";
        for ($i = 0; $i <= 5 - $length; $i++) {
            $nol = "0" . $nol;
        }
        $proforma = $nol . $proforma;

        return $proforma;
    }

    public function discount(Request $request)
    {
        try {
            $sales_order = SalesOrder::query()
                ->where("id", $request->sales_order_id)
                ->with("sales_order_detail", "dealer")
                ->first();

            $dealer_benefit = DealerBenefit::query()
                ->leftJoin("gradings as g", "g.id", "=", "grading_id")
                ->leftJoin("dealers as d", "d.grading_id", "=", "g.id")
                ->join("payment_methods as p", function ($q) {
                    $q->on("p.id", "=", "payment_method_id")
                        ->where("start_period", "<=", Carbon::now())
                        ->where("end_period", ">=", Carbon::now())
                        ->orWhereNull("start_period");
                })
                ->where("d.id", $sales_order->store_id)
                ->where("p.id", $sales_order->payment_method_id)
                ->select("dealer_benefits.*", "d.agency_level_id as dealer_level")
                ->get();

            /* benefit priority selection */
            /*  benefit with periode prioritized */
            $agency_level = $sales_order->dealer->agency_level_id;
            foreach ($dealer_benefit as $key => $value) {
                if (in_array($agency_level, $value->agency_level_id) && $value->start_period != null) {
                    $dealer_benefit = $value;
                } else if (in_array($agency_level, $value->agency_level_id)) {
                    $dealer_benefit = $value;
                } else {
                    $dealer_benefit = $value;
                }
            }

            $potongan = 0;
            $total_amount = $sales_order->sales_order_detail->sum("total");
            $total_amount_fix = $total_amount;
            $benefit_discount = $dealer_benefit->benefit_discount;
            foreach ($benefit_discount as $key => $stage) {
                if ($stage["type"] == "always") {
                    $discount = $total_amount * $stage["discount"]["discount"] / 100;
                    $total_amount -= $discount;
                    $potongan += $discount;
                }
                if ($stage["type"] == "threshold") {
                    if ($total_amount_fix >= $stage["discount"]["minimum_order"]) {
                        $discount = $total_amount * $stage["discount"]["discount"] / 100;
                        $total_amount -= $discount;
                        $potongan += $discount;
                        break;
                    }
                }
            }

            /* ppn after discount */
            $ppn = $total_amount * 0.1;
            $data = (object) [
                "ppn" => $ppn,
                "discount" => $potongan,
                "total_amount" => $total_amount,
                "total_amount_fix" => $total_amount_fix,
                "must_paid" => $total_amount + $ppn,
            ];

            return $data;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * export dealer
     *
     * @return void
     */
    public function export(Request $request)
    {
        ini_set('max_execution_time', 500); //3 minutes
        $datenow = Carbon::now()->format('d-m-Y');
        $data = DirectSalesOrderExport::query()->where('type', '1')->whereHas('invoice', function ($QQQ) use ($request) {
            return $QQQ->whereMonth("created_at", $request->month)->whereYear("created_at", $request->year)
                ->orderBy("created_at", 'desc');
        })->whereNull("deleted_at")->get();

        return $this->response("00", "success", $data);
    }

    public function consideerdToDone(Request $request, $id)
    {
        try {
            $invoice = Invoice::query()
                ->with([
                    "dispatchOrderDetail",
                    "dispatchOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "dispatchOrderDetail",
                            "deliveryOrder" => function ($QQQ) {
                                return $QQQ
                                    ->whereHas("receivingGoods")
                                    ->with([
                                        "receivingGoods" => function ($QQQ) {
                                            return $QQQ
                                                ->with([
                                                    "receivingGoodDetail" => function ($QQQ) {
                                                        return $QQQ->where("status", "delivered");
                                                    },
                                                ]);
                                        },
                                    ]);
                            },
                        ]);
                    },
                    "invoiceProforma",
                    "salesOrderOnly.sales_order_detail",
                ])
                ->findOrFail($id);

            if ($invoice->invoiceProforma) {
                return $this->response("01", "failed", "proforma can't be considered finished, becuse invoice was exist");
            }

            $product_list_detail = [
                "product_id" => null,
                "quantity_sum_loaded" => 0,
            ];

            /**
             * received product
             */
            $product_received_list = [];
            $received_product = collect($invoice->dispatchOrder)->where("deliveryOrder", "!=", null)->values();

            /* dispatch order detail was received */
            $dispacth_order_detail_id_received = [];
            foreach ($received_product as $dispatch_order_has_received) {
                $dispacth_order_detail_id_received = array_merge(collect($dispatch_order_has_received->dispatchOrderDetail)->pluck("id")->toArray(), $dispacth_order_detail_id_received);
            }

            $product_list = [];
            $product_id_list = [];
            $invoice_product = collect($invoice->dispatchOrderDetail)
                ->groupBy("id_product");

            foreach ($invoice_product as $product_id => $dispatch_order) {

                /**
                 * received product
                 */
                $quantity_sum_received = 0;
                foreach ($received_product as $dispatchOrder) {
                    $quantity_sum_received += collect($dispatchOrder->deliveryOrder->receivingGoods->receivingGoodDetail)->where("product_id", $product_id)->sum("quantity");
                }
                $product_received_list[$product_id] = $quantity_sum_received;

                array_push($product_id_list, $product_id);
                $sum_Product_doesnt_received = collect($dispatch_order)
                    ->whereNotIn("id", $dispacth_order_detail_id_received)
                    ->sum("quantity_unit");

                $product_list_detail["product_id"] = $product_id;
                $product_list_detail["quantity_sum_received"] = $product_received_list[$product_id];
                $product_list_detail["quantity_sum_loaded"] = $sum_Product_doesnt_received;
                $product_list[$product_id] = $product_list_detail;
            }

            $sales_order_detail_id = collect($invoice->salesOrderOnly->sales_order_detail);

            /* get product price list */
            $products = Product::query()
                ->with([
                    "priceCheapToExpensive",
                ])
                ->whereIn("id", $product_id_list)
                ->get();

            foreach ($products as $product) {
                $length = count($product->priceCheapToExpensive);
                $product_price = null;

                /* get product price */
                foreach ($product->priceCheapToExpensive as $key => $price) {
                    if ($key == $length - 1) {
                        if (!$product_price) {
                            $product_price = $product->priceCheapToExpensive[$key];
                            break;
                        }
                    }

                    if (
                        $product_list[$product->id]["quantity_sum_loaded"] <= $product->priceCheapToExpensive[$key]->minimum_order
                        && $product_list[$product->id]["quantity_sum_loaded"] > $product->priceCheapToExpensive[$key + 1]->minimum_order
                    ) {
                        $product_price = $product->priceCheapToExpensive[$key + 1];
                    }
                }
                $product_list[$product->id]["price"] = $product_price;
                $sales_Order_detail = collect($sales_order_detail_id)->where("product_id", $product->id)->first();
                $product_list[$product->id]["sales_order_detail_id"] = $sales_Order_detail ? $sales_Order_detail->id : null;
                $product_list[$product->id]["sales_order_detail_discount"] = $sales_Order_detail ? $sales_Order_detail->discount : 0;
                $product_list[$product->id]["sales_order_detail_total"] = $sales_Order_detail ? $sales_Order_detail->total : 0;
            }

            /**
             * update sales order detail
             */
            foreach ($product_list as $product_to_update) {
                $quantity_total = $product_to_update["quantity_sum_loaded"] + $product_to_update["quantity_sum_received"];
                $total = $quantity_total * ($product_to_update["price"] ? $product_to_update["price"]->price : 1);

                $sales_order_detail_updated = SalesOrderDetail::where("id", $product_to_update["sales_order_detail_id"])
                    ->update([
                        "quantity" => $quantity_total,
                        "agency_level_id" => $product_to_update["price"] ? $product_to_update["price"]->agency_level_id : null,
                        "total" => $total - $product_to_update["sales_order_detail_discount"],
                        "unit_price" => $product_to_update["price"] ? $product_to_update["price"]->price : null,
                    ]);
            }

            return $this->response("00", "success", $product_list);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function diagramPerforma(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes

        $thisYear = Carbon::now()->year;
        $data = [
            "proforma" => null,
            "nominal_proforma" => null,
        ];

        $proforma = Invoice::query()->whereYear('created_at', $thisYear)->whereNull("deleted_at")->get();

        $collect_proforma = collect($proforma)->sortByDesc("created_at")->groupBy(
            function ($val) {
                return Carbon::parse($val->created_at)->format('M');
            }
        );

        // return

        // return $collect_proforma;

        $detail = [
            "Jan" => 0,
            "Feb" => 0,
            "Mar" => 0,
            "Apr" => 0,
            "May" => 0,
            "Jun" => 0,
            "Jul" => 0,
            "Aug" => 0,
            "Sep" => 0,
            "Oct" => 0,
            "Nov" => 0,
            "Dec" => 0,

        ];

        $detail_nominal = [
            "Jan" => 0,
            "Feb" => 0,
            "Mar" => 0,
            "Apr" => 0,
            "May" => 0,
            "Jun" => 0,
            "Jul" => 0,
            "Aug" => 0,
            "Sep" => 0,
            "Oct" => 0,
            "Nov" => 0,
            "Dec" => 0,

        ];
        foreach ($collect_proforma as $month => $value) {
            $detail[$month] = $value->count();
            $detail_nominal[$month] = collect($value)->sum("total");
        }

        $array_month = [];
        foreach ($detail as $value) {
            $array_month[] = $value;
        }

        $array_nominal = [];
        foreach ($detail_nominal as $value) {
            $array_nominal[] = $value;
        }

        $data["proforma"]["months"] = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $data["proforma"]["data"] = $array_month;

        $data["nominal_proforma"]["months"] = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $data["nominal_proforma"]["data"] = $array_nominal;

        return $this->response("00", "success", $data);
    }

    public function diagramProformaFiveYear()
    {
        ini_set('max_execution_time', 1500); //3 minutes
        try {
            $thisYear = Carbon::now()->year;
            $data = [];
            $fiveYearsAgo = Carbon::now()->subYears(5);

            $proforma = Invoice::query()->whereYear('created_at', ">", $fiveYearsAgo)->whereNull("deleted_at")->get();
            $collect_proforma = collect($proforma)->sortByDesc("created_at")->groupBy([
                function ($val) {
                    return $val->created_at->format('Y');
                },
                function ($val) {
                    return $val->created_at->format('M');
                },
            ]);

            $detail_nominal = [
                "Jan" => 0,
                "Feb" => 0,
                "Mar" => 0,
                "Apr" => 0,
                "May" => 0,
                "Jun" => 0,
                "Jul" => 0,
                "Aug" => 0,
                "Sep" => 0,
                "Oct" => 0,
                "Nov" => 0,
                "Dec" => 0,

            ];
            // $detail_nominal = [
            //     "data" => null
            // ];

            for ($i = 4; $i >= 0; $i--) {
                $data[Carbon::now()->subYears($i)->format("Y")] = $detail_nominal;
                // $data['data'] = null;
            }

            foreach ($collect_proforma as $year => $month) {
                foreach ($month as $order_on_month => $val) {
                    $data[$year][$order_on_month] = collect($val)->sum("total");
                }
            }

            $detail = [];
            foreach ($data as $year => $value) {
                $array_nominal = [];
                foreach ($value as $val) {
                    $array_nominal[] = $val;
                }
                // $cek[] = [];
                $cek["name"] = $year;
                $cek["data"] = $array_nominal;
                $cek["color"] = null;
                $detail[] = $cek;
            }

            return $this->response("00", "success", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function diagramListProformaFiveYear(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes

        $validator = Validator::make($request->all(), [
            "years" => "array",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalida data send", $validator->errors(), 422);
        }

        try {
            $end_date = Carbon::now()->endOfMonth();
            $data = [];
            $fiveYearsAgo = Carbon::now()->subYears(5);

            if ($request->years) {

                $proforma = Invoice::query()->get()->filter(function ($jadwal) use ($request) {
                    return in_array($jadwal->created_at->format('Y'), $request->years);
                });
            } else {
                $proforma = Invoice::query()
                    ->whereHas("salesOrderOnly")
                    ->whereYear('created_at', ">", $fiveYearsAgo)
                    ->get();
            }
            $collect_proforma = collect($proforma)->sortByDesc("created_at")->groupBy([
                function ($val) {
                    return $val->created_at->format('Y');
                },
                function ($val) {
                    return $val->created_at->format('M');
                },
            ]);

            $detail_nominal = [
                "Jan" => 0,
                "Feb" => 0,
                "Mar" => 0,
                "Apr" => 0,
                "May" => 0,
                "Jun" => 0,
                "Jul" => 0,
                "Aug" => 0,
                "Sep" => 0,
                "Oct" => 0,
                "Nov" => 0,
                "Dec" => 0,
                "total" => 0,
                "count" => 0,
                "avg" => 0,

            ];

            $periodyear = CarbonPeriod::create($fiveYearsAgo, $end_date);

            if ($request->years) {
                $array_years = $request->years;
                foreach ($array_years as $key => $valueyear) {
                    $data[$valueyear] = $detail_nominal;
                }
            } else {

                for ($i = 4; $i >= 0; $i--) {
                    $data[Carbon::now()->subYears($i)->format("Y")] = $detail_nominal;
                }
            }

            foreach ($collect_proforma as $year => $month) {
                $total_per_year = 0;
                $count_value = 0;
                foreach ($month as $order_on_month => $val) {
                    $data[$year][$order_on_month] = collect($val)->sum("total");
                    $total_per_year += $data[$year][$order_on_month];
                    $count_value += count($val);
                }
                $data[$year]["total"] = $total_per_year;
                $data[$year]["count"] = $count_value;
                $data[$year]["avg"] = $total_per_year / $count_value;
            }

            return $this->response("00", "success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function listReceivingGood(Request $request, $invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);
            $deliveryOrder = DeliveryOrder::with([
                'receivingGoods.receivedBy.position',
            ])
                ->wherehas('receivingGoods', function ($q) {
                    $q->where('delivery_status', 2);
                })
                ->whereHas('dispatchOrder', function ($q) use ($invoiceId) {
                    return $q->where('invoice_id', $invoiceId);
                });

            if ($request->has('sort')) {
                if ($request->sort['field'] == 'delivery_order') {
                    $deliveryOrder->orderBy('delivery_order_number', $request->sort['direction']);
                }

                if ($request->has('sort') && $request->sort['field'] == 'date_received') {
                    $deliveryOrder->orderBy(function ($query) {
                        $query->select('date_received')
                            ->from('receiving_goods')
                            ->whereColumn('delivery_orders.id', 'receiving_goods.delivery_order_id')
                            ->where('delivery_status', 2)
                            ->whereNull('receiving_goods.deleted_at')
                            ->limit(1);
                    }, $request->sort['direction']);
                }

                if ($request->has('sort') && $request->sort['field'] == 'received_by') {
                    $deliveryOrder->orderBy(function ($query) {
                        $query->select('personels.name')
                            ->from('receiving_goods')
                            ->join('personels', 'personels.id', '=', 'receiving_goods.received_by')
                            ->whereColumn('delivery_orders.id', 'receiving_goods.delivery_order_id')
                            ->where('delivery_status', 2)
                            ->whereNull('receiving_goods.deleted_at')
                            ->limit(1);
                    }, $request->sort['direction']);
                }
            }
            $deliveryOrder = $deliveryOrder->get();

            $response = [
                "invoice" => $invoice,
                "delivery_order" => $deliveryOrder,
            ];
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function updateCustom(Request $request, $invoiceId)
    {
        $validator = Validator::make($request->all(), [
            "delivery_status" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalida data send", $validator->errors(), 422);
        }

        try {
            Invoice::where('id', $invoiceId)->update([
                'delivery_status' => $request->delivery_status,
            ]);
            $invoice = Invoice::find($invoiceId);
            return $this->response("00", "success", $invoice);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }
}
