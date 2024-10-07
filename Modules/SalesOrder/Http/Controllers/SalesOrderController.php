<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Filters\StatusFilter;
use App\Models\ExportRequests;
use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use App\Traits\DistributorTrait;
use App\Traits\FollowUpTrait;
use App\Traits\GmapsLinkGenerator;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use App\Traits\SupervisorCheck;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentDayColor;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\ProductMandatory;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Distributor\Entities\DistributorContract;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealerV2\Entities\SubDealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerMinimalis;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Marketing;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\SalesOrderExport;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;
use Modules\SalesOrderV2\Entities\SalesOrderMinimalis;
use Modules\SalesOrder\Actions\Order\ConfirmOrderAction;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Events\DeletedSalesOrderEvent;
use Modules\SalesOrder\Events\SalesOrderReturn;
use Modules\SalesOrder\Http\Requests\SalesOrderRequest;
use Modules\SalesOrder\Jobs\CancelledOrderJob;
use Modules\SalesOrder\Rules\PaymentMethodMarketingRule;
use Modules\SalesOrder\Traits\SalesOrderTrait;
use Modules\SalesOrder\Transformers\OrderInMonthCollectionResource;
use Pricecurrent\LaravelEloquentFilters\EloquentFilters;

class SalesOrderController extends Controller
{
    use ResponseHandler, ChildrenList, SupervisorCheck, MarketingArea;
    use GmapsLinkGenerator;
    use DistributorTrait;
    use DistributorStock;
    use SalesOrderTrait;
    use FollowUpTrait;

    public function __construct(
        SalesOrderMinimalis $sales_order_minimalis,
        SalesOrderDetail $sales_order_detail,
        PaymentMethod $payment_method,
        SubDealerV2 $sub_dealerv2,
        SalesOrder $sales_order,
        SubDealer $sub_dealer,
        Marketing $marketing,
        Personel $personel,
        DealerV2 $dealerv2,
        Dealer $dealer,
    ) {
        $this->sales_order_minimalis = $sales_order_minimalis;
        $this->sales_order_detail = $sales_order_detail;
        $this->payment_method = $payment_method;
        $this->subdealerv2 = $sub_dealerv2;
        $this->sales_order = $sales_order;
        $this->sub_dealer = $sub_dealer;
        $this->marketing = $marketing;
        $this->personel = $personel;
        $this->dealerv2 = $dealerv2;
        $this->dealer = $dealer;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        ini_set('max_execution_time', 1500);
        $validator = Validator::make($request->all(), [
            "year" => "required_with:quartal",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        /* get dealer id list */
        $dealers = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->pluck("id")->toArray();

        /* get sub dealer id list */
        $sub_dealers = DB::table('sub_dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->pluck("id")->toArray();
        $stores = array_unique(array_merge($dealers, $sub_dealers));

        try {
            $personel_id = auth()->user()->personel_id;
            $status_default = ['confirmed', "pending", "returned"];
            $status = EloquentFilters::make([new StatusFilter($status_default)]);
            $status_filter = EloquentFilters::make([new StatusFilter($request->status)]);
            $personel_store = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $personel_id)->get();

            if ($request->has("personel_branch")) {
                unset($request["scope_supervisor"]);
            }
            $sales_order = $this->sales_order->query()
                ->with([
                    'salesCounter',
                    'subDealer.adressDetail',
                    'dealer.adress_detail',
                    'paymentMethod',
                    'distributor',
                    'personel',
                    'payments',
                    'invoice',
                    'invoice.invoiceProforma',
                    'invoice.payment', 'statusFee',
                    'sales_order_detail' => function ($QQQ) {
                        return $QQQ->withAggregate("product", "name")->orderBy("product_name", "asc");
                    },
                ])
                ->withAggregate("invoiceOnly", "created_at")
                ->withCount("sales_order_detail")

            /* filter by status */
                ->when($request->has("status") && !$request->has("canceled_only"), function ($q) use ($status_filter, $request) {
                    return $q->whereIn("sales_orders.status", $request->status);
                    // return $q->filter($status_filter);
                })

            /* deafult status */
                ->when(!$request->has("status") && !$request->has("canceled_only"), function ($q) use ($status) {
                    return $q->filter($status);
                })

            /* filterby type order, direct = 1, indirect = 2 */
                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })

            /* filter by model, dealer = 1, sub dealer = 2 */
                ->when($request->has("model"), function ($q) use ($request) {
                    return $q->whereIn("model", $request->model);
                })

                ->when($request->has("byDateBetween"), function ($q) use ($request) {
                    return $q->whereIn("created_at", $request->byDateBetween);
                })

                ->when($request->has("order_number"), function ($q) use ($request) {
                    return $q->where("order_number", "like", "%" . $request->order_number . "%");
                })

                ->when($request->has('start_date') && $request->has('end_date'), function ($Q) use ($request) {
                    if (in_array(1, $request->type) && count($request->type) == 1) {
                        return $Q->whereHas("invoice", function ($Q) use ($request) {
                            return $Q->whereBetween("created_at", [$request->start_date, $request->end_date]);
                        });
                    } else if (in_array(2, $request->type) && count($request->type) == 1) {
                        return $Q->whereBetween("date", [$request->start_date, $request->end_date]);
                    } else {
                        return $Q->where(function ($Q) use ($request) {
                            return $Q
                                ->where(function ($Q) use ($request) {
                                    return $Q
                                        ->where("type", "1")
                                        ->whereHas("invoice", function ($Q) use ($request) {
                                            return $Q->whereBetween("created_at", [$request->start_date, $request->end_date]);
                                        });
                                })
                                ->orWhere(function ($Q) use ($request) {
                                    return $Q
                                        ->where("type", "2")
                                        ->whereBetween("date", [$request->start_date, $request->end_date]);
                                });
                        });
                    }
                })

            /* filter by dealer_id */
                ->when($request->has("dealer_id"), function ($qqq) use ($request) {
                    return $qqq->whereHas("dealerv2", function ($q) use ($request) {
                        return $q
                            ->where("dealer_id", $request->dealer_id)
                            ->orWhere("id", $request->dealer_id);
                    });
                })

            /* filter by dealer_name */
                ->when($request->has("dealer_name"), function ($qqq) use ($request) {
                    $dealer_id = DB::table("dealers")->whereNull("deleted_at")->where("name", "like", "%" . $request->dealer_name . "%")->get()->pluck("id");
                    return $qqq->whereHas("dealerv2", function ($q) use ($dealer_id) {
                        return $q->whereIn("store_id", $dealer_id);
                    });
                })

                ->when($request->has("cust_name"), function ($qqq) use ($request) {
                    return $qqq->where(function ($QQQ) use ($request) {
                        return $QQQ->whereHas("dealerv2", function ($q) use ($request) {
                            return $q->where("name", "like", "%" . $request->cust_name . "%");
                        })->orWhereHas("subDealer", function ($q) use ($request) {
                            return $q->where("name", "like", "%" . $request->cust_name . "%");
                        });
                    });
                })
            /* filter by marketing */
                ->when($request->has("marketing"), function ($qqq) use ($request) {
                    return $qqq->whereHas("personel", function ($q) use ($request) {
                        return $q->whereIn("personels.id", $request->marketing);
                    });
                })

                ->when($request->has("marketing_id"), function ($qqq) use ($request) {
                    return $qqq->whereHas("personel", function ($q) use ($request) {
                        return $q->where("personels.id", $request->marketing_id);
                    });
                })

            /* filter by marketing */
                ->when($request->has("marketing_name"), function ($qqq) use ($request) {
                    return $qqq->whereHas('personel', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->marketing_name . "%");
                    });
                })

                ->when($request->has("date_canceled"), function ($qqq) use ($request) {
                    return $qqq->whereDate("updated_at", $request->date_canceled);
                })

                ->when($request->month, function ($QQQ) use ($request) {
                    if (in_array(1, $request->type) && count($request->type) == 1) {
                        return $QQQ->whereHas("invoice", function ($Q) use ($request) {
                            return $Q
                                ->whereMonth("created_at", $request->month);
                        });
                    } else if (in_array(2, $request->type) && count($request->type) == 1) {
                        return $QQQ->whereMonth("date", $request->month);
                    } else {
                        return $QQQ->where(function ($Q) use ($request) {
                            return $Q
                                ->where(function ($Q) use ($request) {
                                    return $Q
                                        ->where("type", "1")
                                        ->whereHas("invoice", function ($Q) use ($request) {
                                            return $Q
                                                ->whereMonth("created_at", $request->month);
                                        });
                                })
                                ->orWhere(function ($Q) use ($request) {
                                    return $Q
                                        ->where("type", "2")
                                        ->whereMonth("date", $request->month);
                                });
                        });
                    }
                })

            /* filter by no order */
                ->when($request->has("id"), function ($qqq) use ($request) {
                    return $qqq->where("id", "like", "%" . $request->id . "%");
                })

            /* filter by year of order */
                ->when($request->has("year"), function ($q) use ($request) {
                    return $q->consideredOrderByYear($request->year);
                })

            /* filter by quartal */
                ->when($request->quartal > 0, function ($q) use ($request) {
                    if ($request->quartal == 1) {
                        $month = 1;
                    } elseif ($request->quartal == 2) {
                        $month = 4;
                    } elseif ($request->quartal == 3) {
                        $month = 7;
                    } else {
                        $month = 10;
                    }

                    $startDate = Carbon::createFromFormat('Y', $request->year)->month($month)->startOfQuarter()->format('Y-m-d H:i:s');
                    $endDate = Carbon::createFromFormat('Y', $request->year)->month($month)->endOfQuarter()->format('Y-m-d H:i:s');
                    return $q->where(function ($Q) use ($request, $startDate, $endDate) {
                        return $Q
                            ->where(function ($Q) use ($request, $startDate, $endDate) {
                                return $Q
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($Q) use ($startDate, $endDate) {
                                        return $Q
                                            ->whereDate("created_at", ">=", $startDate)->whereDate("created_at", "<=", $endDate);
                                    });
                            })
                            ->orWhere(function ($Q) use ($request, $startDate, $endDate) {
                                return $Q
                                    ->where("type", "2")
                                    ->whereDate("date", ">=", $startDate)->whereDate("date", "<=", $endDate);
                            });
                    });
                    // return $q->whereDate("date", ">=", $startDate)->whereDate("date", "<=", $endDate);
                })

            /* filter by store id */
                ->when($request->store_id, function ($q) use ($request) {
                    return $q->where(function ($Q) use ($request) {
                        return $Q
                            ->whereHas("subDealer", function ($query) use ($request) {
                                return $query->where("dealer_id", $request->store_id);
                            })
                            ->orWhere(function ($Q) use ($request) {
                                return $Q->where("sales_orders.store_id", $request->store_id);
                            });
                    });
                })

            /* filter by distributor */
                ->when($request->distributor_id, function ($q) use ($request) {
                    return $q->where("distributor_id", $request->distributor_id);
                })

            /* filter distributor or retailer */
                ->when($request->has("filter_type"), function ($QQQ) use ($request) {
                    return $QQQ->filterDirectIndirectDistributorRetailerInYear($request->year ? $request->year : now()->format("Y"), $request->month ? $request->month : null, $request->store_id ? $request->store_id : null, $request->personel_id ? $request->personel_id : null, $request->filter_type);
                })

            /* filter by proforma number */
                ->when($request->has("proforma"), function ($qqq) use ($request) {
                    return $qqq->whereHas("invoice", function ($q) use ($request) {
                        return $q->where("invoice", "like", "%" . $request->proforma . "%");
                    });
                })

            /* filter by reference number */
                ->when($request->has("reference_number"), function ($qqq) use ($request) {
                    return $qqq->where("reference_number", "like", "%" . $request->reference_number . "%");
                })

            /* filter by created_at */
                ->when($request->has("date"), function ($QQQ) use ($request) {
                    if (!$request->has("type")) {
                        $request->merge([
                            "type" => [1, 2],
                        ]);
                    }

                    return $QQQ
                        ->when(in_array("1", $request->type) && in_array("2", $request->type), function ($QQQ) use ($request) {
                            return $QQQ
                                ->where(function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->when($request->has("status"), function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->whereIn("sales_orders.status", $request->status);
                                        })

                                        ->when($request->has("model"), function ($q) use ($request) {
                                            return $q->whereIn("model", $request->model);
                                        })
                                        ->whereHas("invoice", function ($QQQ) use ($request) {
                                            return $QQQ->whereDate("created_at", $request->date);
                                        })
                                        ->orWhere(function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->when($request->has("status"), function ($QQQ) use ($request) {
                                                    return $QQQ
                                                        ->whereIn("sales_orders.status", $request->status);
                                                })

                                                ->when($request->has("model"), function ($q) use ($request) {
                                                    return $q->whereIn("model", $request->model);
                                                })
                                                ->whereNull("link")
                                                ->whereDoesntHave("invoice")
                                                ->whereDate("updated_at", $request->date);
                                        });
                                })
                                ->orWhere(function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->where(function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->whereNotNull("link")
                                                ->whereDoesntHave("invoice")
                                                ->whereDate("created_at", $request->date);
                                        })
                                        ->when($request->has("status"), function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->whereIn("sales_orders.status", $request->status);
                                        })

                                        ->when($request->has("model"), function ($q) use ($request) {
                                            return $q->whereIn("model", $request->model);
                                        });
                                });
                        })
                        ->when(in_array("1", $request->type) && !in_array("2", $request->type), function ($QQQ) use ($request) {
                            return $QQQ
                                ->directSaleByDate($request->date)
                                ->when($request->has("status"), function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->whereIn("sales_orders.status", $request->status);
                                })

                                ->when($request->has("model"), function ($q) use ($request) {
                                    return $q->whereIn("model", $request->model);
                                });
                        })
                        ->when(in_array("2", $request->type) && !in_array("1", $request->type), function ($QQQ) use ($request) {
                            return $QQQ
                                ->indirectSaleByDate($request->date)
                                ->when($request->has("status"), function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->whereIn("sales_orders.status", $request->status);
                                })

                                ->when($request->has("model"), function ($q) use ($request) {
                                    return $q->whereIn("model", $request->model);
                                });
                        });
                })

            /* filter by created_at */
                ->when($request->has("created_at"), function ($qqq) use ($request) {
                    return $qqq->whereDate("created_at", $request->created_at);
                })

            /* filter by supervisor id */
                ->when($request->scope_supervisor, function ($Q) use ($request) {
                    return $Q->supervisor();
                })

                ->when($request->has('distributor_name'), function ($Q) use ($request) {
                    return $Q->whereHas('distributor', function ($Q) use ($request) {
                        return $Q->where('name', 'like', '%' . $request->distributor_name . "%");
                    });
                })

            /* filter by personel id */
                ->when($request->has("personel_id"), function ($Q) use ($stores, $request) {
                    return $Q

                    /**
                 * sales order will handled by new marketing
                 * if sales order does not done, it's mean
                 * direct sales not settle or delivery
                 * order does not done
                 */
                        ->when(in_array("1", $request->type) && in_array("2", $request->type), function ($QQQ) use ($request, $stores) {
                            return $QQQ

                            /* direct sale not settle or not done */
                                ->where(function ($QQQ) use ($request, $stores) {
                                    return $QQQ
                                        ->whereIn("store_id", $stores)
                                        ->whereNull("link")
                                        ->where(function ($QQQ) use ($request, $stores) {
                                            return $QQQ
                                                ->whereHas("invoiceHasOne", function ($QQQ) use ($request, $stores) {
                                                    return $QQQ
                                                        ->where(function ($QQQ) use ($request) {
                                                            return $QQQ
                                                                ->where("delivery_status", "2")
                                                                ->orWhere("payment_status", "!=", "settle");
                                                        })
                                                        ->when($request->has("date"), function ($QQQ) use ($request) {
                                                            return $QQQ
                                                                ->whereDate("created_at", $request->date);
                                                        });
                                                })
                                                ->orWhereDoesntHave("invoiceHasOne");
                                        })

                                        ->when($request->has("model"), function ($q) use ($request) {
                                            return $q->whereIn("model", $request->model);
                                        });
                                })

                                /* indirect or direct settle and done */
                                ->orWhere(function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->when($request->has("date"), function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->whereDate("created_at", $request->date);
                                        })
                                        ->when($request->has("status"), function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->whereIn("sales_orders.status", $request->status);
                                        })

                                        ->when($request->has("model"), function ($q) use ($request) {
                                            return $q->whereIn("model", $request->model);
                                        })

                                        ->where("personel_id", $request->personel_id)
                                        ->whereDoesntHave("invoiceHasOne")
                                        ->whereNotNull("link");
                                });
                        })

                        ->when(in_array("1", $request->type) && !in_array("2", $request->type), function ($QQQ) use ($request, $stores) {
                            return $QQQ
                                ->whereIn("store_id", $stores)
                                ->whereNull("link")
                                ->where(function ($QQQ) use ($request, $stores) {
                                    return $QQQ
                                        ->whereHas("invoiceHasOne", function ($QQQ) use ($request, $stores) {
                                            return $QQQ
                                                ->where(function ($QQQ) {
                                                    return $QQQ
                                                        ->where("delivery_status", "2")
                                                        ->orWhere("payment_status", "!=", "settle");
                                                });
                                        })
                                        ->orWhereDoesntHave("invoiceHasOne");
                                });
                        })

                        /* indirect and direct settle or done */
                        ->when(in_array("2", $request->type) && !in_array("1", $request->type), function ($QQQ) use ($request, $stores) {
                            return $QQQ
                                ->where("sales_orders.personel_id", $request->personel_id)
                                ->whereDoesntHave("invoiceHasOne")
                                ->whereNotNull("link");
                        });
                })

            /* filter by payment status */
                ->when($request->unsettle_only == "yes", function ($Q) use ($request, $stores) {
                    return $Q
                        ->unsettlePayment()
                        ->whereHas("dealer", function ($QQQ) use ($request, $stores) {
                            return $QQQ
                                ->whereHas("personel")
                                ->when($request->has("scope_supervisor"), function ($QQQ) {
                                    return $QQQ->supervisor();
                                })
                                ->when($request->has("personel_id"), function ($QQQ) use ($request, $stores) {
                                    return $QQQ
                                        ->whereIn("sales_orders.store_id", $stores);

                                    /**
                         * old
                         *
                         * ->where("personel_id", $request->personel_id);
                         */
                                });
                        });
                })

            /* filter by region */
                ->when($request->has("region_id"), function ($Q) use ($request) {
                    return $Q->region($request->region_id);
                })

            /* filter by sub region */
                ->when($request->has("sub_region_id"), function ($Q) use ($request) {
                    return $Q->subRegion($request->sub_region_id);
                })

            /* filter by region */
                ->when($request->has("scope_region"), function ($Q) use ($request) {
                    return $Q->region($request->scope_region);
                })

            /* filter by sub region */
                ->when($request->has("scope_sub_region"), function ($Q) use ($request) {
                    return $Q->subRegion($request->scope_sub_region);
                })

                ->when($request->has("canceled_only"), function ($QQQ) use ($request) {
                    return $QQQ->with("salesOrderHistoryChange.personel.position")->where('sales_orders.status', 'canceled');
                })

            /* filter sales order by personel branch */
                ->when($request->personel_branch, function ($QQQ) {
                    return $QQQ->PersonelBranch();
                })

            /* filter by proforma or dealer name or dealer id */
                ->when($request->has("by_proforma_or_dealer_name_id"), function ($QQQ) use ($request) {
                    return $QQQ->byProformaDealerIdDealerName($request->by_proforma_or_dealer_name_id);
                })
                ->withAggregate(["salesOrderDetail as total_qty_x_unit_price" => function ($query) {
                    return $query->select(DB::raw("sum(COALESCE(quantity,0))*sum(COALESCE(unit_price,0))"));
                }], 0)

                ->excludeBlockedDealer()
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("order_type")) {
                        $sort_type = $request->order_type;
                    }
                    if ($request->sorting_column == 'marketing_name') {
                        return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'sales_orders.personel_id'), $request->order_type);
                    } else if ($request->sorting_column == 'distibutor_name') {
                        return $QQQ
                            ->leftJoin('dealers', 'sales_orders.distributor_id', '=', 'dealers.id')
                            ->leftJoin('sub_dealers', 'sales_orders.distributor_id', '=', 'sub_dealers.id')
                            ->orderByRaw("CASE WHEN dealers.name IS NOT NULL THEN dealers.name WHEN sub_dealers.name IS NOT NULL THEN sub_dealers.name ELSE NULL END " . $request->order_type);
                    } else if ($request->sorting_column == 'dealer_id' || $request->sorting_column == 'cust_id') {
                        $direction = $request->order_type ?? "asc";

                        return $QQQ
                            ->leftJoin("dealers as d", "d.id", "sales_orders.store_id")
                            ->leftJoin("sub_dealers as sd", "sd.id", "sales_orders.store_id")
                            ->orderByRaw("if(sales_orders.model = 1, d.dealer_id, sd.sub_dealer_id) $direction")
                            ->select("sales_orders.*");
                    } else if ($request->sorting_column == 'fee_status') {
                        return $QQQ->orderBy(StatusFee::select('status_fee_id')->whereColumn('status_fee.id', 'sales_orders.status_fee_id'), $request->order_type);
                    } else if ($request->sorting_column == 'sort_by_customer_id') {
                        return $QQQ->orderBy(DealerV2::select('dealer_id')->whereColumn('dealers.id', 'sales_orders.store_id'), $request->order_type);
                    } else if ($request->sorting_column == 'sort_by_distributor_id') {
                        return $QQQ->orderBy(DealerV2::select('dealer_id')->whereColumn('dealers.id', 'sales_orders.distributor_id'), $request->order_type);
                    } else if ($request->sorting_column == 'sort_by_sales_order_history_change_created') {
                        return $QQQ->orderBy(function ($query) {
                            $query->selectRaw('MAX(created_at)')
                                ->from('sales_order_history_change_statuses')
                            // ->join('payments', 'invoices.id', '=', 'payments.invoice_id')
                                ->whereColumn('sales_order_history_change_statuses.sales_order_id', 'sales_orders.id');
                        }, $request->order_type);
                    } else if ($request->sorting_column == 'sort_by_sales_order_history_change_name') {
                        return $QQQ->orderBy(function ($query) {
                            $query->selectRaw('personels.name')
                                ->from('sales_order_history_change_statuses')
                                ->join('personels', 'sales_order_history_change_statuses.personel_id', '=', 'personels.id')
                                ->groupBy("sales_order_history_change_statuses.sales_order_id")
                                ->whereColumn('sales_order_history_change_statuses.sales_order_id', 'sales_orders.id');
                        }, $request->order_type);
                    } else if ($request->sorting_column == 'order_date') {
                        $direction = $request->order_type ?? "asc";
                        return $QQQ
                            ->orderByRaw("if(sales_orders.date is not null, sales_orders.date, i.created_at) $direction")
                            ->leftJoin("invoices as i", "i.sales_order_id", "sales_orders.id")
                            ->whereNull("i.deleted_at")
                            ->select("sales_orders.*");
                    } else if ($request->sorting_column == 'invoice') {
                        return $QQQ->orderBy(Invoice::select('invoice')->whereColumn('sales_orders.id', 'invoices.sales_order_id')->groupBy("invoices.sales_order_id"), $request->order_type);
                    } else if ($request->sorting_column == 'invoice_proforma_number') {
                        return $QQQ
                            ->orderBy(
                                DB::table('invoices as i')
                                    ->selectRaw("ipn.invoice_proforma_number")
                                    ->whereNull("ipn.deleted_at")
                                    ->whereNull("i.deleted_at")
                                    ->leftJoin('invoice_proformas as ipn', function ($join) {
                                        $join->on('ipn.invoice_id', '=', 'i.id')
                                            ->whereNull('ipn.deleted_at');
                                    })
                                    ->whereColumn('i.sales_order_id', 'sales_orders.id')
                                    ->groupBy("i.sales_order_id"),
                                $request->order_type
                            );
                    } else if ($request->sorting_column == 'invoice_total') {
                        return $QQQ->orderBy(Invoice::select('total')->whereColumn('sales_orders.id', 'invoices.sales_order_id'), $request->order_type);
                    } else if ($request->sorting_column == 'payment_total') {
                        return $QQQ->orderBy(function ($query) {
                            $query->selectRaw('SUM(payments.nominal)')
                                ->from('invoices')
                                ->whereNull("payments.deleted_at")
                                ->whereNull("invoices.deleted_at")
                                ->join('payments', 'invoices.id', '=', 'payments.invoice_id')
                                ->whereColumn('invoices.sales_order_id', 'sales_orders.id');
                        }, $request->order_type);
                    } else if ($request->sorting_column == 'last_payment') {
                        return $QQQ->orderBy(function ($query) {
                            $query->selectRaw('MAX(payment_date)')
                                ->from('payments as p')
                                ->whereNull("p.deleted_at")
                                ->whereNull("i.deleted_at")
                                ->join('invoices as i', 'i.id', '=', 'p.invoice_id')
                                ->whereColumn('i.sales_order_id', 'sales_orders.id');
                        }, $request->order_type);
                    } else if ($request->sorting_column == 'total_qty_x_unit_price') {
                        $direction = $request->order_type ?? "asc";
                        return $QQQ
                            ->orderBy(
                                DB::table('sales_order_details as sod')
                                    ->selectRaw("COALESCE(sum(sod.quantity)*sum(sod.unit_price), 0)")
                                    ->whereNull("sod.deleted_at")
                                    ->whereColumn('sod.sales_order_id', 'sales_orders.id')
                                    ->groupBy("sod.sales_order_id"),
                                $direction
                            );
                    } else if ($request->sorting_column == 'remaining') {
                        $direction = $request->order_type ?? "asc";
                        // dd($direction);
                        return $QQQ
                            ->orderBy(
                                DB::table('invoices as i')
                                    ->selectRaw("(i.total + i.ppn) - if(sum(p.nominal) > 0, sum(p.nominal), 0)")
                                    ->whereNull("p.deleted_at")
                                    ->whereNull("i.deleted_at")
                                    ->leftJoin('payments as p', function ($join) {
                                        $join->on('p.invoice_id', '=', 'i.id')
                                            ->whereNull('p.deleted_at');
                                    })
                                    ->whereColumn('i.sales_order_id', 'sales_orders.id')
                                    ->groupBy("i.sales_order_id"),
                                $direction
                            );
                    } else if ($request->sorting_column == 'dealer_name') {
                        return $QQQ
                            ->leftJoin('dealers AS d', 'sales_orders.store_id', '=', 'd.id')
                            ->leftJoin('sub_dealers AS s', 'sales_orders.store_id', '=', 's.id')
                            ->orderByRaw("CASE WHEN d.name IS NOT NULL THEN d.name WHEN s.name IS NOT NULL THEN s.name ELSE NULL END " . $request->order_type);
                    } else {
                        return $QQQ->orderBy($request->sorting_column, $sort_type);
                    }
                });

            if ($request->has("dealer_name")) {
                $sales_order = $this->hasChild($sales_order, $request);
            }

            $sales_order = $sales_order->paginate($request->limit ? $request->limit : 15);

            if ($request->has('groupColumn')) {
                $sales_order = $sales_order->groupBy($request->groupColumn);
            }

            return $this->response('00', 'sales order index', $sales_order);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get sales order index', [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]);
        }
    }

    public function hasChild($model, $request)
    {
        $childQuery = $request->dealer_name;
        return $model->whereHas('dealer', function ($q) use ($childQuery) {
            $q->where('name', 'like', '%' . $childQuery . "%");
        });
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('salesorder::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $personel_id = null;
        $counter_id = null;
        $is_office = false;
        $model = null;
        $model_data = null;
        $distributor_id = null;
        $store = null;
        $status_fee = null;
        $follow_up_days = null;

        $validator = Validator::make($request->all(), [
            'payment_method_id' => [
                'max:255',
                new PaymentMethodMarketingRule(null),

            ],
            'store_id' => 'required',
            'type' => 'required',
            'model' => 'required',
            "reference_number" => "unique:sales_orders,reference_number,NULL,id,deleted_at,NULL",
            "sales_mode" => [
                Rule::in(["office", "follow_up", "marketing"]),
            ],
        ]);

        if ($validator->fails()) {
            return $this->responseAsJson("04", "invalid data send", $validator->errors(), 422);
        }

        if ($request->model == "2") {
            $validator_2 = Validator::make($request->all(), [
                "distributor_id" => "required",
                "reference_number" => "required",
                "link" => "required",
            ]);

            if ($validator_2->fails()) {
                return $this->responseAsJson("04", "invalid data send", $validator_2->errors(), 422);
            }

            $store = $this->sub_dealer->findOrFail($request->store_id);
        } else {
            $store = $this->dealer->findOrFail($request->store_id);
            $status_fee = $store ? $store->status_fee : null;
        }

        /**
         * marketing purchaser is marketing on sub dealer or dealer
         * if marketing on dealer / sub dealer is null
         * order will set to null marketing,
         */
        if ($store) {
            if ($store->personel_id) {
                $personel_id = $store->personel_id;
            }
        }

        try {
            if ($request->type == "1") {
                $model = "1";
                $model_data = $this->dealer->findOrfail($request->store_id);

                /* follow up days counter */
                if (in_array($request->sales_mode, ["office", "follow_up"])) {
                    if (!auth()->user()->hasAnyRole("super-admin")) {
                        $follow_up_days = $this->salesOrderfollowUpDays($request->store_id, $request->model);
                    } else {
                        $follow_up_days = $this->salesOrderfollowUpDays($request->store_id, $request->model);
                    }

                    if ($request->sales_mode == "follow_up") {
                        $counter_id = $request->has("counter_id") ? $request->counter_id : auth()->user()->personel_id;
                    }
                }

                $is_office = $request->sales_mode == "office" ? true : false;

            } else {
                $model = $request->model;
                if ($model == 2) {
                    $model_data = $this->sub_dealer->findOrfail($request->store_id);
                } else {
                    $model_data = $this->dealer->findOrfail($request->store_id);
                }

                $distributor_id = $request->distributor_id;
                $store = $this->dealer->findOrFail($request->distributor_id);
            }

            $sales_order = $this->sales_order->create([
                'store_id' => $request->store_id,
                'personel_id' => $personel_id,
                'counter_id' => $counter_id,
                'follow_up_days' => $follow_up_days,
                'model' => $model,
                'type' => $request->type,
                'distributor_id' => $distributor_id,
                'agency_level_id' => $request->agency_level_id,
                'reference_number' => $request->reference_number,
                'link' => $request->link,
                'date' => $request->has('date') ? $request->date : null,
                'status_fee_id' => $status_fee,
                'is_office' => $is_office,
                "status" => $request->status ?: "draft",
                "sales_mode" => $request->sales_mode
            ]);

            if ($request->type == 1) {
                $export_request_check = DB::table('export_requests')->where("type", "sales_order_direct")->where("status", "requested")->first();
                $export_request_detail_check = DB::table('export_requests')->where("type", "sales_order_direct_detail")->where("status", "requested")->first();

                $type = "sales_order_direct";
                $type_sales_order = "sales_order_direct_detail";
            } else {
                $export_request_check = DB::table('export_requests')->where("type", "sales_order_indirect")->where("status", "requested")->first();
                $export_request_detail_check = DB::table('export_requests')->where("type", "sales_order_indirect_detail")->where("status", "requested")->first();

                $type = "sales_order_indirect";
                $type_sales_order = "sales_order_indirect_detail";
            }

            if (!$export_request_check) {
                ExportRequests::Create([
                    "type" => $type,
                    "status" => "requested",
                    "created_at" => now(),
                ]);
            }

            if (!$export_request_detail_check) {
                ExportRequests::Create([
                    "type" => $type_sales_order,
                    "status" => "requested",
                    "created_at" => now(),
                ]);
            }

            /**
             * default grade order on store
             * default graduing form sub dealer
             */
            $grading_id = null;
            if ($sales_order->model == "2") {
                $sub_dealer_grading = Grading::query()
                    ->where("default", "1")
                    ->first();

                if ($sub_dealer_grading) {
                    $grading_id = $sub_dealer_grading?->id;
                }
            }

            /* grading dealer */
            if ($sales_order->model == "1") {
                $dealer = DB::table('dealers')->where("id", $sales_order->store_id)->first();
                $grading_id = $dealer?->grading_id;
            }

            /* handover and status fee check for marking order as freeze */
            $sales_order->is_marketing_freeze = $sales_order?->personel?->status == "2" ? true : false;
            $sales_order->change_locked = $request->change_locked;
            $sales_order->grading_id = $grading_id;
            $sales_order->save();

            if ($sales_order->status == "submited") {
                $sales_order->submited_by = auth()->user()->personel_id;
                $sales_order->submited_at = now();
                $sales_order->save();
            }

            return $this->response('00', 'sales order detail', $sales_order);
        } catch (\Throwable $th) {
            return $this->response('00', 'failed to save sales order', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id, Request $request)
    {

        $this->sales_order->findOrFail($id);
        try {
            $sales_order = $this->sales_order->query()
                ->where('id', $id)
                ->with([
                    'salesCounter',
                    'subDealer',
                    'dealer' => function ($QQQ) {
                        return $QQQ->with([
                            "adressDetail" => function ($QQQ) {
                                return $QQQ->where("type", "dealer");
                            },
                        ]);
                    },
                    'sales_order_detail' => function ($QQQ) use ($request) {
                        return $QQQ->withAggregate("product", "name")
                            ->when($request->sorting_column == "product_name", function ($query) use ($request) {
                                $sort_type = "asc";
                                if ($request->has("direction")) {
                                    $sort_type = $request->direction;
                                }
                                return $query->orderBy("product_name", $sort_type);
                            });
                    },
                    'personel',
                    'paymentMethod',
                    'invoice' => function ($QQQ) {
                        return $QQQ
                            ->with([
                                "dispatchOrder",
                            ]);
                    },
                    'subDealer',
                    'distributor',
                    "returnedBy.position",
                    "statusFee",
                ])
                ->orderBy("updated_at", "desc")
                ->first();
            return $this->response('00', 'sales order detail', $sales_order);
        } catch (\Throwable $th) {
            return $this->response('00', 'failed to display sales order detail', $th->getMessage(), 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('salesorder::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = sales order
     * @return Renderable
     */
    public function update(SalesOrderRequest $request, $id)
    {
        try {
            $sales_order = SalesOrder::findOrFail($id);
            $sales_order_update = $this->sales_order->query()
                ->with([
                    "invoice" => function ($QQQ) {
                        return $QQQ
                            ->with([
                                "invoiceProforma",
                                "dispatchOrder" => function ($QQQ) {
                                    return $QQQ
                                        ->orderBy("created_at")
                                        ->with([
                                            "deliveryOrder" => function ($QQQ) {
                                                return $QQQ->where("status", "send");
                                            },
                                        ]);
                                },
                            ]);
                    },
                    "personel",
                    "salesCounter.position",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "statusFee",
                        ]);
                    },
                    "subDealer" => function ($QQQ) {
                        return $QQQ->with([
                            "statusFee",
                        ]);
                    },
                    "grading",
                    "sales_order_detail",
                ])
                ->findOrFail($id);

            /**
             * update payment method rule
             */
            $dispatch_order_has_delivery_order = null;
            $data = $request->all();

            $dealer = $this->dealer->find($sales_order_update->store_id);

            DB::beginTransaction();

            unset($data["gmaps_link"]);
            if ($request->has("canceled_at")) {
                unset($data["canceled_at"]);
            }

            if ($request->has("follow_up_days")) {
                unset($data["follow_up_days"]);
            }

            if ($request->has("status_fee_id")) {
                unset($data["status_fee_id"]);
            }

            if ($request->latitude && $request->longitude) {
                $data["delivery_location"] = $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude);
            }

            /* update order */
            foreach ($data as $key => $value) {
                $sales_order_update[$key] = $value;
            }

            // Pas tuku, check toko tersebut district_idnya ada applicator_id atau tidak.. Jika ada masukkan. Pas konfirmasi
            $applicator_id = DB::table('sales_orders as so')
                ->select('so.store_id', 'so.id', 'awd.district_id', 'mad.applicator_id')
                ->leftJoin('address_with_details as awd', 'so.store_id', '=', 'awd.parent_id')
                ->leftJoin('marketing_area_districts as mad', 'mad.district_id', '=', 'awd.district_id')
                ->where("so.id", $id)
                ->whereNull('so.deleted_at')
                ->whereNull('awd.deleted_at')
                ->whereNull('mad.deleted_at')
                ->orderByDesc('mad.applicator_id')->first()?->applicator_id;

            $sales_order_update->applicator_id = $applicator_id ?: null;
            $sales_order_update->save();

            if ($sales_order_update->status == "confirmed") {

                //update if personel frezee
                if ($dealer && $dealer->personel->status == '2') {
                    $dealer->is_marketing_freeze = true;
                }

                /**
                 * fee sharing marketing, applicator
                 * and sales counter
                 */

                if ($request->type == 1) {
                    $export_request_check = DB::table('export_requests')->where("type", "sales_order_direct")->where("status", "requested")->first();
                    $export_request_detail_check = DB::table('export_requests')->where("type", "sales_order_direct_detail")->where("status", "requested")->first();

                    $type = "sales_order_direct";
                    $type_sales_order = "sales_order_direct_detail";
                } else {
                    $export_request_check = DB::table('export_requests')->where("type", "sales_order_indirect")->where("status", "requested")->first();
                    $export_request_detail_check = DB::table('export_requests')->where("type", "sales_order_indirect_detail")->where("status", "requested")->first();

                    $type = "sales_order_indirect";
                    $type_sales_order = "sales_order_indirect_detail";
                }

                // $type = $request->type = 1 ? "sales_order_direct" : "sales_order_indirect";

                if (!$export_request_check) {
                    ExportRequests::Create([
                        "type" => $type,
                        "status" => "requested",
                        "created_at" => now(),
                    ]);
                }

                if (!$export_request_detail_check) {
                    ExportRequests::Create([
                        "type" => $type_sales_order,
                        "status" => "requested",
                        "created_at" => now(),
                    ]);
                }

                ExportRequests::updateOrCreate([
                    "type" => "all_sales",
                    "status" => "requested",
                ], [
                    "created_at" => now(),
                ]);

                // add contest poin active
                if ($sales_order_update->type == 2) {

                    /*
                    |-------------------------------------------------
                    | ON CONFIRM ORDER
                    |-----------------------------------------
                    | 1. return history check
                    | 2. marketing fee reguler
                    | 3. marketing fee target
                    | 4. contest point
                    | 5. grading of order
                     *
                     */
                    $confirm_order = new ConfirmOrderAction;
                    $confirm_order($sales_order_update);
                }

                $confirmed_personnel = $sales_order_update ? $sales_order_update->personel_id : null;
            }

            if ($sales_order_update->status == "submited") {

                if ($sales_order_update->type == "1") {
                    /* follow up days counter */
                    if (in_array($request->sales_mode, ["office", "follow_up"])) {
                        $sales_order_update->follow_up_days = $this->salesOrderfollowUpDays($sales_order_update->store_id, $sales_order_update->model);
    
                        if ($request->sales_mode == "follow_up") {
                            $sales_order_update->counter_id = $request->counter_id?? auth()->user()->personel_id;
                        }   
                    }
                    $sales_order_update->is_office =  $request->sales_mode == "office" ? true : false;
                }

                $sales_order_update->submited_by = auth()->user()->personel_id;
                $sales_order_update->submited_at = now();
                $sales_order_update->save();
            }

            if ($sales_order_update->status == "canceled") {

                /* canceled order event */
                Bus::chain([
                    new CancelledOrderJob($sales_order_update, auth()->user()->personel_id, ($request->has("canceled_at") ? $request->canceled_at : now())),
                ])->dispatch();

                // DirectSalesRejectedNotificationEvent::dispatch($sales_order_update);
            }

            /**
             * trigger event if any product
             * is returned
             */
            if ($sales_order_update->status == "returned") {
                $sales_order_on_return = SalesOrderReturn::dispatch($sales_order_update);

                if (!$request->has("return")) {
                    $sales_order_update->return = $sales_order_update->type == "2" ? $sales_order_update->date : $sales_order_update->invoice->created_at;
                    $sales_order_update->save();
                }
            }

            $history = SalesOrderHistoryChangeStatus::create([
                "sales_order_id" => $sales_order_update->id,
                "type" => $sales_order_update->type,
                "status" => $sales_order_update->status,
                "personel_id" => auth()->user()->personel_id,
                "note" => "set to" . $sales_order_update->status . " in " . Carbon::now()->format("Y-m-d"),
            ]);

            DB::commit();

            return response()->json([
                'response_code' => '00',
                'response_message' => 'sales order updated',
                'data' => $sales_order_update,
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->responseAsJson("01", "server errror", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $sales_order = $this->sales_order
                ->with([
                    "sales_order_detail" => function ($QQQ) {
                        return $QQQ->with([
                            "sales_order" => function ($QQQ) {
                                return $QQQ->with(["invoice"]);
                            },
                        ]);
                    },
                    "invoice",
                ])
                ->findOrFail($id);
            $sales_Order_before_delete = $sales_order;
            $sales_order->delete();

            /* event if sales order deleted */
            $sales_order_event = DeletedSalesOrderEvent::dispatch($sales_Order_before_delete);
            DB::commit();
            return $this->response("00", "sales order deleted", $sales_order);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed to delete sales order", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]);
        }
    }

    /**
     * check all product total price before order
     *
     * @param [type] $sales_order_id
     * @return void
     */
    public function totalPrice(Request $request)
    {
        try {
            $total = $this->sales_order_detail->query()
                ->where('sales_order_id', $request->sales_order_id)
                ->sum('total');

            $discount = 0;
            return $this->response("00", "sales order total amount", $total);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get sales order total amount", $th->getMessage());
        }
    }

    public function payment_method_check($payment_method_id)
    {
        $payment_method = $this->payment_method->find($payment_method_id);

        return $payment_method;
    }

    public function detailRekapPerFiveYear(Request $request, $personel_id = null)
    {
        $validator = Validator::make($request->all(), [
            "year" => "required",
            "order_type" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }
        ini_set('max_execution_time', 500); //3 minutes

        try {
            $sales_orders = $this->sales_order->query()
                ->with([
                    'salesCounter',
                    'subDealer.adressDetail',
                    'dealer' => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                            "adress_detail",
                        ]);
                    },
                    'invoice',
                ])
                ->consideredOrderPerMonth($request->year, $request->month)
                ->withCount("sales_order_detail")
                ->when($request->has("personel_id"), function ($q) use ($request) {
                    return $q->where("personel_id", $request->personel_id);
                })

            /* filter  */

            /* filter by no order */
                ->when($request->has("id"), function ($qqq) use ($request) {
                    return $qqq->where("id", "like", "%" . $request->id . "%");
                })

            /* filter by supervisor id */
                ->when($request->scope_supervisor, function ($Q) use ($request) {
                    return $Q->supervisor();
                })
                ->when($request->limit, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })
            // ->paginate($request->limit ? $request->limit : 10)
                ->get()
                ->sortBy(function ($order) {
                    if ($order->type == "2") {
                        return $order->date;
                    } else {
                        return $order->invoice->created_at;
                    }
                })
                ->values();

            /* filter distributor, retailer, direct, indirect */
            $sales_orders = $this->filterDirectIndirectDistributorRetailer($sales_orders, $request->has("order_type") ? $request->order_type : [1, 2, 3, 4]);

            return $this->response('00', 'sales order index', new OrderInMonthCollectionResource($sales_orders));
        } catch (\Throwable $th) {
            return $this->responseAsJson("01", "failed", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ], 500);
        }
    }

    /**
     * response
     *
     * @param [type] $code
     * @param [type] $message
     * @param [type] $data
     * @return void
     */
    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * group sales order by month and year
     * @param [type] $store_id
     * @return void
     */
    public function salesOrderGroupByStoreYearly(Request $request, $store = null)
    {
        try {
            $fiveYearsAgo = Carbon::now()->subYears(5);

            if ($request->has("year")) {
                $fiveYearsAgo = $date = Carbon::createFromDate($request->year, 1, 1)->subYears(5);
            }

            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];

            $recap = [];

            if ($request->has("year")) {
                for ($i = 4; $i >= 0; $i--) {
                    $recap[Carbon::createFromDate($request->year, 1, 1)->subYears($i)->format("Y")] = $month;
                }
            } else {
                for ($i = 4; $i >= 0; $i--) {
                    $recap[Carbon::now()->subYears($i)->format("Y")] = $month;
                }
            }

            $sales_orders = $this->sales_order
                ->with([
                    "invoice",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "distributorContract",
                            "DitributorContract",
                        ]);
                    },
                ])
                ->where('store_id', $store)
                ->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4])
                ->consideredOrderFromYear($fiveYearsAgo)
                ->consideredOrderToYear($request->year ?? now()->year)
                ->get();

            /* filter distributor retailer direct indirect */
            $sales_orders = $this->filterDirectIndirectDistributorRetailer($sales_orders, $request->has("order_type") ? $request->order_type : [1, 2, 3, 4]);

            $recap = collect($recap)
                ->map(function ($template_year, $year) use ($sales_orders) {
                    $recap_year = $this->dataMapTotalPerYearPerMonth($sales_orders)
                        ->filter(fn($recap_order_year, $year_order) => $year_order == $year)
                        ->values();

                    if ($recap_year->count() > 0) {
                        $template_year = collect($template_year)->merge($recap_year->first());
                    }

                    return $template_year;
                });

            return $this->response("00", "direct sales recap on 5 years", $recap);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to recap sales on 5 years", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    public function salesOrderGroupByStoreYearlyDirectAndIndirect(Request $request, $personel_id = null)
    {
        ini_set('max_execution_time', 500); //3 minutes
        try {
            $fiveYearsAgo = Carbon::now()->subYears(5);

            $validator = Validator::make($request->all(), [
                "order_type" => [
                    "required",
                    "array",
                ],
            ]);

            if ($validator->fails()) {
                return $this->responseAsJson("04", "invalid data send", $validator->errors(), 422);
            }

            /**
             * distributor contract
             */
            $active_distributors = DistributorContract::query()
                ->whereYear("contract_start", ">=", $fiveYearsAgo)
                ->get();

            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
            $recap = [];

            for ($i = 4; $i >= 0; $i--) {
                $recap[Carbon::now()->subYears($i)->format("Y")] = $month;
            }

            $nomor = 1;
            $sales_orders = $this->sales_order
                ->with([
                    "invoice",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])
                ->where('personel_id', $personel_id)
                ->when(collect($request->order_type)->contains(fn($type) => $type <= 2) && !collect($request->order_type)->contains(fn($type) => $type >= 3), function ($QQQ) {
                    return $QQQ->where("type", 1);
                })
                ->when(collect($request->order_type)->contains(fn($type) => $type >= 3) && !collect($request->order_type)->contains(fn($type) => $type <= 2), function ($QQQ) {
                    return $QQQ->where("type", 2);
                })
                ->when(collect($request->order_type)->contains(fn($type) => $type >= 1 && $type <= 4), function ($QQQ) {
                    return $QQQ->whereIn("type", [1, 2]);
                })
                ->consideredOrderFromYear($fiveYearsAgo)
                ->get()
                ->sortBy(function ($order) {
                    if ($order->type == "2") {
                        return $order->date;
                    } else {
                        return $order->invoice->created_at;
                    }
                });

            /* direct to distributor */
            $direct_distributor = $sales_orders
                ->where("type", 1)
                ->filter(function ($order) use ($request) {
                    if ($this->isOrderInsideDistributorContract($order) && in_array(1, $request->order_type)) {
                        return $order;
                    }
                });

            /* direct to retailer */
            $direct_retailer = $sales_orders
                ->where("type", 1)
                ->filter(function ($order) use ($request) {
                    if (!$this->isOrderInsideDistributorContract($order) && in_array(2, $request->order_type)) {
                        return $order;
                    }
                });

            /* indirect to distributor */
            $indirect_distributor = $sales_orders
                ->where("type", 2)
                ->filter(function ($order) use ($request) {
                    if ($this->isOrderInsideDistributorContract($order) && in_array(3, $request->order_type)) {
                        return $order;
                    }
                });

            /* indirect to retailer */
            $indirect_retailer = $sales_orders
                ->where("type", 2)
                ->filter(function ($order) use ($request) {
                    if (!$this->isOrderInsideDistributorContract($order) && in_array(4, $request->order_type)) {
                        return $order;
                    }
                });

            $sales_orders = $direct_distributor
                ->concat($direct_retailer)
                ->concat($indirect_distributor)
                ->concat($indirect_retailer)
                ->groupBy([
                    function ($val) {
                        if ($val->type == 1) {
                            return $val->invoice->created_at->format('Y');
                        } else {
                            return Carbon::parse($val->date)->format('Y');
                        }
                    },
                    function ($val) {
                        if ($val->type == 1) {
                            return $val->invoice->created_at->format('M');
                        } else {
                            return Carbon::parse($val->date)->format('M');
                        }
                    },
                ])
                ->map(function ($order_per_year, $year) {
                    $order_per_year = collect($order_per_year)->map(function ($order_per_month, $month) {
                        return collect($order_per_month)->sum(function ($order) {
                            return SalesOrderDetail::where('sales_order_id', $order->id)->sum(DB::raw('(quantity - COALESCE(returned_quantity, 0)) * unit_price - COALESCE(discount, 0)'));
                            // if ($order->type == "2") {
                            //     return $order->total;pA
                            // } else {
                            //     return $order->invoice->total;
                            // }
                        });
                    });

                    return $order_per_year;
                });

            $recap_marketing = collect($recap)
                ->map(function ($recap_per_year, $year) use (&$sales_orders) {
                    if (in_array($year, collect($sales_orders)->keys()->toArray())) {
                        $recap_per_year = collect($recap_per_year)->merge($sales_orders[$year]);
                    }

                    return $recap_per_year;
                });

            return $this->response("00", "success get recap sales on  five years", $recap_marketing);
        } catch (\Throwable $th) {
            return $this->responseAsJson("01", "failed to recap sales on  five years", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    /**
     * sales order, recap per month on this year
     * @param [type] $store_id
     * @return void
     */
    public function salesOrderAllGroupByStoreYearly()
    {
        $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
        $data = $this->sales_order
            ->where('status', "confirmed")
            ->with([
                "invoice",
            ])
            ->where(function ($Q) {
                return $Q
                    ->where(function ($Q) {
                        return $Q
                            ->where("type", "1")
                            ->whereHas("invoice", function ($Q) {
                                return $Q
                                    ->whereYear("created_at", Carbon::now())
                                    ->where("payment_status", "settle");
                            });
                    })
                    ->orWhere(function ($Q) {
                        return $Q
                            ->where("type", "2")
                            ->whereYear("date", Carbon::now());
                    });
            })
            ->get();

        $indirect = $data->where("type", "2")->sortBy("date")->groupBy([
            function ($val) {
                return Carbon::parse($val->created_at)->format('Y');
            },
            function ($val) {
                return Carbon::parse($val->created_at)->format('M');
            },
        ]);

        $direct = $data->where("type", "1")->sortBy("invoice.created_at")->groupBy([
            function ($val) {
                return $val->invoice->created_at->format('Y');
            },
            function ($val) {
                return $val->invoice->created_at->format('M');
            },
        ]);

        $report[Carbon::now()->format("Y")] = $month;

        foreach ($indirect as $year => $value) {
            foreach ($value as $month => $val) {
                $report[$year][$month] = collect($val)->sum('total');
            }
        }

        foreach ($direct as $year => $value) {
            foreach ($value as $month => $val) {
                $report[$year][$month] += collect($val)->sum('invoice.total');
            }
        }

        $data = $report;

        return compact('data');
    }
    /**
     * group sales order by month and year
     * @param [type] $store_id
     * @return void
     */
    public function salesOrderAllGroupByStoreYearlyConfirmed()
    {
        try {
            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
            $data = $this->sales_order
                ->with([
                    "invoice",
                ])
                ->where('status', "confirmed")
                ->where(function ($Q) {
                    return $Q
                        ->where(function ($Q) {
                            return $Q
                                ->where("type", "1")
                                ->whereHas("invoice", function ($Q) {
                                    return $Q
                                        ->whereYear("created_at", Carbon::now())
                                        ->where("payment_status", "settle");
                                });
                        })
                        ->orWhere(function ($Q) {
                            return $Q
                                ->where("type", "2")
                                ->whereYear("date", Carbon::now());
                        });
                })
                ->get();

            $indirect = $data->where("type", "2")->sortBy("date")->groupBy([
                function ($val) {
                    return Carbon::parse($val->created_at)->format('Y');
                },
                function ($val) {
                    return Carbon::parse($val->created_at)->format('M');
                },
            ]);

            $direct = $data->where("type", "1")->sortBy("invoice.created_at")->groupBy([
                function ($val) {
                    return $val->invoice->created_at->format('Y');
                },
                function ($val) {
                    return $val->invoice->created_at->format('M');
                },
            ]);

            $report[Carbon::now()->format("Y")] = $month;

            foreach ($indirect as $year => $value) {
                foreach ($value as $month => $val) {
                    $report[$year][$month] = collect($val)->sum('total');
                }
            }

            foreach ($direct as $year => $value) {
                foreach ($value as $month => $val) {
                    $report[$year][$month] += collect($val)->sum('invoice.total');
                }
            }

            $data = $report;
            return $this->response("00", "sales recap on this years", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to recap sales on this years", $th->getMessage());
        }
    }
    /**
     * group sales order by month and year, invoice list
     * @param [type] $store_id, $date
     * @return void
     */
    public function salesOrderAllGroupByStoreYearlyConfirmedWithStoreId(Request $request, $id = null)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes
            $sales_orders = $this->sales_order
                ->with([
                    "invoice" => function ($QQQ) {
                        return $QQQ->with([
                            "payment",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "distributorContract",
                            "ditributorContract",
                        ]);
                    },
                    "distributor",
                    "paymentMethod",
                    "statusFee",
                ])
                ->where('store_id', $id)
                ->where("type", 1)

            /* filter duistributor retailer */
                ->filterDirectIndirectDistributorRetailerInYear(($request->year ? $request->year : now()->format("Y")), ($request->month ? $request->month : null), $id, null, $request->has("filter_type") ? $request->filter_type : [1, 2, 3, 4])

            /* filter by year or month*/
                ->when($request->has("year"), function ($QQQ) use ($request) {
                    $QQQ->when($request->has("month"), function ($sub) use ($request) {
                        return $QQQ->consideredOrderPerMonth($request->year, $request->month);
                    });

                    return $QQQ->consideredOrderByYear($request->year);
                })

            /* default result if there has no parameter */
                ->when(!$request->has("year"), function ($QQQ) {
                    return $QQQ->consideredOrderByYear(now()->format("Y"));
                })

            /* filter by payment status*/
                ->when($request->has("payment_status"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("invoice", function ($QQQ) use ($request) {
                        return $QQQ->whereIn("payment_status", $request->payment_status);
                    });
                })
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("order_type")) {
                        $sort_type = $request->order_type;
                    }
                    if ($request->sorting_column == 'marketing_name') {
                        return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'sales_orders.personel_id'), $request->order_type);
                    } else if ($request->sorting_column == 'distibutor_name') {
                        return $QQQ->orderBy(DealerV2::select('name')->whereColumn('dealers.id', 'sales_orders.distributor_id'), $request->order_type);
                    } else if ($request->sorting_column == 'no_proforma') {
                        return $QQQ->orderBy(Invoice::select('invoice')->whereColumn('sales_orders.id', 'invoices.sales_order_id'), $request->order_type);
                    } else if ($request->sorting_column == 'proforma_date') {
                        return $QQQ->orderBy(Invoice::select('created_at')->whereColumn('sales_orders.id', 'invoices.sales_order_id'), $request->order_type);
                    } else if ($request->sorting_column == 'proforma_total') {
                        return $QQQ->orderBy(Invoice::select('total')->whereColumn('sales_orders.id', 'invoices.sales_order_id'), $request->order_type);
                    } else if ($request->sorting_column == 'status_fee') {
                        return $QQQ->orderBy(StatusFee::select('name')->whereColumn('status_fee.id', 'sales_orders.status_fee_id'), $request->order_type);
                    } else if ($request->sorting_column == 'remaining') {
                        $direction = $request->order_type ?? "asc";
                        return $QQQ
                            ->orderBy(
                                DB::table('invoices as i')
                                    ->selectRaw("(i.total + i.ppn) - if(sum(p.nominal) > 0, sum(p.nominal), 0)")
                                    ->whereNull("p.deleted_at")
                                    ->whereNull("i.deleted_at")
                                    ->leftJoin('payments as p', function ($join) {
                                        $join->on('p.invoice_id', '=', 'i.id')
                                            ->whereNull('p.deleted_at');
                                    })
                                    ->whereColumn('i.sales_order_id', 'sales_orders.id')
                                    ->groupBy("p.invoice_id"),
                                $direction
                            );
                    } else if ($request->sorting_column == 'last_payment') {
                        $direction = $request->order_type ?? "asc";
                        return $QQQ->orderBy(function ($query) {
                            $query->selectRaw('MAX(payment_date)')
                                ->from('payments as p')
                                ->whereNull("p.deleted_at")
                                ->whereNull("i.deleted_at")
                                ->join('invoices as i', 'i.id', '=', 'p.invoice_id')
                                ->whereColumn('i.sales_order_id', 'sales_orders.id');
                        }, $direction);
                    } else {
                        return $QQQ->orderBy($request->sorting_column, $sort_type);
                    }
                })
                ->orderBy('type')
                ->paginate($request->limit ? $request->limit : 15);

            $paymentDayColor = PaymentDayColor::select("id", "min_days", "max_days", "bg_color", "text_color")->get();

            $sales_orders->map(function ($data) use ($paymentDayColor) {
                if (count($paymentDayColor) === 0) {
                    $data->invoice->bg_color = "FFFFFF";
                    $data->invoice->text_color = "000000";
                    return;
                }

                foreach ($paymentDayColor as $color) {
                    if ($color->max_days && $data->invoice->payment_time >= $color->min_days && $data->invoice->payment_time <= $color->max_days) {
                        $data->invoice->bg_color = $color->bg_color;
                        $data->invoice->text_color = $color->text_color;
                        return;
                    } elseif (!$color->max_days && $data->invoice->payment_time >= $color->min_days) {
                        $data->invoice->bg_color = $color->bg_color;
                        $data->invoice->text_color = $color->text_color;
                        return;
                    }
                }

                // Jika tidak ada korespondensi warna yang ditemukan
                $data->invoice->bg_color = "FFFFFF";
                $data->invoice->text_color = "000000";
            });

            return $this->response("00", "invoice list/sales order per dealer on specific range", $sales_orders);
        } catch (\Throwable $th) {
            return $this->responseAsJson("01", "failed to get invoice list per dealer", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    /**
     * product distribution sales order by month and year
     * @param [type] $store_id, $date
     * @return void
     */
    public function productSalesByStore(Request $request, $id = null)
    {
        /**
         * settle or not
         */
        $fiveYearsAgo = Carbon::now()->subYears(5);

        if ($request->has("max_year")) {
            $fiveYearsAgo = Carbon::createFromDate($request->max_year, 1, 1)->subYears(5);
        }

        $model = "1";
        if ($request->has("model")) {
            $model = $request->model;
        }

        try {
            $data = $this->sales_order
                ->with([
                    "invoice",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "distributorContract",
                        ]);
                    },
                ])
                ->where('store_id', $id)
                ->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4])
                ->consideredOrderFromYear($fiveYearsAgo)
                ->consideredOrderToYear($request->max_year ?? now()->year)
                ->where("model", $model)
                ->get();

            /* filter distributor or retailer */
            $sales_orders = $this->filterDirectIndirectDistributorRetailer($data, $request->has("order_type") ? $request->order_type : [1, 2, 3, 4]);

            $sales_order_detail = $this->sales_order_detail
                ->with([
                    "product",
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice",
                        ]);
                    },
                ])
                ->whereIn('sales_order_id', $sales_orders->pluck("id")->toArray())
                ->select("sales_order_details.*")
                ->get();

            $sales_order_detail_grouped = $sales_order_detail
                ->groupBy([
                    function ($order_detail) {
                        return $order_detail->product_id;
                    },
                    function ($order_detail) {
                        return confirmation_time($order_detail->salesOrder)->format('Y');
                    },
                ]);

            $report = [];
            $year_list = [];
            $test = [];
            $year_list["product"] = null;
            $year_list["total"] = null;

            if ($request->has("max_year")) {
                for ($i = 4; $i >= 0; $i--) {
                    $year = Carbon::createFromDate($request->max_year, 1, 1)->subYears($i)->format("Y");
                    $year_list[Carbon::createFromDate($request->max_year, 1, 1)->subYears($i)->format("Y")] = 0;
                }
            } else {
                for ($i = 4; $i >= 0; $i--) {
                    $year = Carbon::now()->subYears($i)->format("Y");
                    $year_list[Carbon::now()->subYears($i)->format("Y")] = 0;
                }
            }

            foreach ($sales_order_detail_grouped as $product => $year) {
                $test[$product] = $year_list;
                foreach ($year as $product_on_year => $val) {
                    if ($product_on_year <= $fiveYearsAgo->year) {
                        continue;
                    }
                    $test[$product]["product"] = $val[0]->product;
                    $test[$product][$product_on_year] = (collect($val)->sum('quantity') - collect($val)->sum('returned_quantity'));
                    $test[$product]["total"] += (collect($val)->sum('quantity') - collect($val)->sum('returned_quantity'));
                }
            }
            $report = collect($test)->sortBy('total')->reverse()->toArray();

            if ($request->sorting_column == "sort_by_total_product_by_year" && $request->year) {
                if ($request->direction == "asc") {
                    $report = collect($report)->sortBy($request->year)->toArray();
                } else {
                    $report = collect($report)->sortByDesc($request->year)->toArray();
                }
            } elseif ($request->sorting_column == "product_name") {
                if ($request->direction == "asc") {
                    $report = collect($report)->sortBy("product.name", SORT_NATURAL | SORT_FLAG_CASE)->toArray();
                } else {
                    $report = collect($report)->sortByDesc("product.name", SORT_NATURAL | SORT_FLAG_CASE)->toArray();
                }
            } elseif ($request->sorting_column == "sort_by_total_product") {
                if ($request->direction == "asc") {
                    $report = collect($report)->sortBy("total")->toArray();
                } else {
                    $report = collect($report)->sortByDesc("total")->toArray();
                }
            }
            return $this->response("00", "product pick up recap on 5 yearssss", $report);
        } catch (\Throwable $th) {
            return $this->responseAsJson("01", "failed tp recap product pick up", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    /**
     * product distribution recap by product
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function productSalesByProduct(Request $request, $id = null)
    {
        $fiveYearsAgo = Carbon::now()->subYears(5);

        if ($request->has("year")) {
            $fiveYearsAgo = Carbon::createFromDate($request->year, 1, 1)->subYears(5);
        }

        $model = "1";
        if ($request->has("model")) {
            $model = $request->model;
        }

        try {
            $data = $this->sales_order
                ->with([
                    "invoice",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "distributorContract",
                        ]);
                    },
                ])
                ->where('store_id', $id)
                ->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4])
                ->consideredOrderFromYear($fiveYearsAgo)
                ->consideredOrderToYear($request->year ?? now()->year)
                ->where("model", $model)
                ->get();

            /* filter distributor or retailer */
            $sales_orders = $this->filterDirectIndirectDistributorRetailer($data, $request->has("order_type") ? $request->order_type : [1, 2, 3, 4]);

            $sales_order_detail = $this->sales_order_detail
                ->with([
                    "product",
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice",
                        ]);
                    },
                ])
                ->whereIn('sales_order_id', $sales_orders->pluck("id")->toArray())
                ->join('products', 'product_id', '=', 'products.id')
                ->where("product_id", $request->product_id)
                ->select("sales_order_details.*", "products.id")
                ->get()
                ->groupBy([
                    function ($val) {
                        return confirmation_time($val->salesOrder)->format('Y');
                    },
                    function ($val) {
                        return confirmation_time($val->salesOrder)->format('M');
                    },
                ]);

            $report = [];
            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];

            if ($request->has("year")) {
                for ($i = 4; $i >= 0; $i--) {
                    $report[Carbon::createFromDate($request->year, 1, 1)->subYears($i)->format("Y")] = $month;
                    $report[Carbon::createFromDate($request->year, 1, 1)->subYears($i)->format("Y")]["total"] = 0;

                }
            } else {
                for ($i = 4; $i >= 0; $i--) {
                    $report[Carbon::now()->subYears($i)->format("Y")] = $month;
                    $report[Carbon::now()->subYears($i)->format("Y")]["total"] = 0;
                }
            }

            foreach ($sales_order_detail as $year => $month) {
                foreach ($month as $product_on_month => $val) {
                    $report[$year][$product_on_month] = (collect($val)->sum('quantity') - collect($val)->sum('returned_quantity'));
                    $report[$year]["total"] += (collect($val)->sum('quantity') - collect($val)->sum('returned_quantity'));
                }
            }

            return $this->response("00", "specific product pick up recap on 5 years", $report);
        } catch (\Throwable $th) {
            return $this->response("01", "failed tp recap product pick up", $th->getMessage());
        }
    }

    public function productMandatory(Request $request, $id = null)
    {
        $monthBefore = Carbon::now()->startOfYear();
        $monthEnd = Carbon::now()->subMonth()->endOfMonth();

        if ($id == null) {
            $personel_id = $request->personel_id;
        } else {
            $personel_id = $id;
        }

        if (!empty($request->year)) {
            $year = $request->year;
        } else {
            $year = now()->format("Y");
        }

        try {
            $sales_orders = $this->sales_order
                ->with([
                    "invoice",
                    "sales_order_detail",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])
                ->where('personel_id', $personel_id)
                ->consideredOrderByYear($year)

            /* filter  */
                ->when(collect($request->order_type)->contains(fn($type) => $type <= 2) && !collect($request->order_type)->contains(fn($type) => $type >= 3), function ($QQQ) {
                    return $QQQ->where("type", 1);
                })
                ->when(collect($request->order_type)->contains(fn($type) => $type >= 3) && !collect($request->order_type)->contains(fn($type) => $type <= 2), function ($QQQ) {
                    return $QQQ->where("type", 2);
                })
                ->when(collect($request->order_type)->contains(fn($type) => $type >= 1 && $type <= 4), function ($QQQ) {
                    return $QQQ->whereIn("type", [1, 2]);
                })

                ->whereHas("sales_order_detail")
                ->when($request->has("model"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("model", $request->model);
                })
                ->get();

            /* filter distributor, retailer, direct, indirect */
            $sales_orders = $this->filterDirectIndirectDistributorRetailer($sales_orders, $request->order_type);

            $sales_order_detail = SalesOrderDetail::query()
                ->with([
                    "product",
                    "sales_order" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice",
                        ]);
                    },
                    "product_mandatory" => function ($QQQ) use ($year) {
                        return $QQQ
                            ->with([
                                "productMandatory" => function ($QQQ) {
                                    return $QQQ->with([
                                        "productGroup",
                                        "product",
                                    ]);
                                },
                                "product",
                            ])
                            ->where("period_date", $year);
                    },
                ])
                ->whereHas("product_mandatory", function ($QQQ) use ($year) {
                    return $QQQ
                        ->where("period_date", $year)
                        ->whereHas("productMandatory");
                })
                ->whereIn("sales_order_id", $sales_orders->pluck("id")->toArray())
                ->get()
                ->sortBy(function ($order_detail) {
                    if ($order_detail->sales_order->type == "2") {
                        return Carbon::parse($order_detail->sales_order->date);
                    }
                    return $order_detail->sales_order->invoice->created_at;
                })
                ->map(function ($order_detail) {
                    $order_detail->product_group_id = $order_detail?->product_mandatory?->productMandatory?->product_group_id;
                    $order_detail->month = $order_detail->sales_order->type == "2" ? Carbon::parse($order_detail->sales_order->date)->format("m") : $order_detail->sales_order->invoice->created_at->format("m");
                    return $order_detail;
                });

            /* recap achievement product mandatory */
            $product_mandatories = ProductMandatory::query()
                ->with([
                    "productMember" => function ($QQQ) {
                        return $QQQ->with("product");
                    },
                    "productGroup",
                ])
                ->whereHas("productGroup", function ($QQQ) {
                    return $QQQ->with("product");
                })
                ->where("period_date", $year)
                ->get()
                ->groupBy("product_group_id")
                ->map(function ($product_mandatory, $group_id) use ($sales_order_detail, $year, $personel_id) {
                    $currentMonth = date('m');
                    $previousMonths = implode(',', array_map(function ($i) {
                        return str_pad($i, 2, '0', STR_PAD_LEFT);
                    }, range(1, $currentMonth - 1)));

                    $query_current_month = $this->queryProductMandatory($group_id, $personel_id, $year, now()->format('m'));
                    $query_month_before = $this->queryProductMandatory($group_id, $personel_id, $year, $previousMonths);
                    $query_achievement = $this->queryProductMandatory($group_id, $personel_id, $year, "01,02,03,04,05,06,07,08,09,10,11,12");

                    $current_month = !empty($query_current_month) ? $query_current_month[0]->volume : 0;
                    $month_before = !empty($query_month_before) ? $query_month_before[0]->volume : 0;
                    $achievement = !empty($query_achievement) ? $query_achievement[0]->volume : 0;

                    $detail = [
                        "name" => $product_mandatory[0]->productGroup->name,
                        "month_before" => $month_before,
                        "month_now" => $current_month,
                        "target" => $product_mandatory[0]->target,
                        "target_in" => $product_mandatory[0]->productMember->pluck("product")->first()->metric_unit,
                        "product" => $product_mandatory[0]->productMember->pluck("product"),
                        "progress" => $achievement > 0 ? ($achievement / $product_mandatory[0]->target * 100) : 0,
                    ];

                    return $detail;
                });

            return $this->response("00", "specific target product mandatory recap on before and month now", $product_mandatories);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to Get specific target product mandatory month before and month now", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
                "file" => $th->getFile(),
            ]);
        }
    }

    private function queryProductMandatory($productId, $personelId, $year, $month)
    {
        $query = DB::select(DB::raw("select
                YEAR(COALESCE(i.created_at, so.date)) sales_year,
                pd.product_group_id,
                pd.mandatory_product,
                ROUND(
                    SUM(
                        (sd.quantity - IFNULL(sd.returned_quantity, 0)) * pd.volume
                    ),
                    2
                ) volume
            FROM
                sales_order_details as sd
                /* JOIN SALES */
                JOIN sales_orders as so on so.id = sd.sales_order_id and so.status in ('confirmed','returned','pending')
                left JOIN invoices as i on i.sales_order_id = so.id
                and i.deleted_at is null
                and canceled_at is null
                left JOIN distributor_contracts as dc on dc.dealer_id = so.store_id
                and so.model = 1
                and dc.deleted_at is null
                and COALESCE(i.created_at, so.date) BETWEEN dc.contract_start
                AND dc.contract_end
                /* JOIN PRODUCT MANDATORY */
                JOIN (
                    select
                        pd.*,
                        pg.id product_group_id,
                        pg.name `mandatory_product`,
                        pm.period_date `mandatory_year`,
                        pm.target `mandatory_target`
                    from
                        products as pd
                        join product_group_members as pgm on pgm.product_id = pd.id
                        and pgm.deleted_at is null
                        join product_groups as pg on pg.id = pgm.product_group_id
                        and pg.deleted_at is null
                        join product_mandatories as pm on pm.product_group_id = pg.id
                        and pm.deleted_at is null
                    group by
                        pd.id,
                        pm.period_date
                ) as pd on pd.id = sd.product_id
                and pd.mandatory_year = YEAR(COALESCE(i.created_at, so.date))
                /* JOIN TOKO */
                JOIN (
                    select
                        ad.parent_id as store_id,
                        IF(ad.type = 'dealer', 1, 2) model,
                        area.*,
                        id.name as district,
                        ic.name as city,
                        ip.name as province,
                        CONCAT_WS(', ', id.name, ic.name, ip.name) as toko_lokasi
                    from
                        address_with_details as ad
                        LEFT JOIN (
                            select
                                md.district_id,
                                ms.id as sub_region_id,
                                ms.name as sub_region,
                                mr.id as region_id,
                                mr.name as region
                            from
                                marketing_area_districts as md
                                JOIN marketing_area_sub_regions as ms on ms.id = md.sub_region_id
                                and ms.deleted_at is null
                                JOIN marketing_area_regions as mr on mr.id = ms.region_id
                                and mr.deleted_at is null
                            Where
                                md.deleted_at is null
                        ) as area on area.district_id = ad.district_id
                        JOIN indonesia_districts as id on id.id = ad.district_id
                        JOIN indonesia_cities as ic on ic.id = ad.city_id
                        JOIN indonesia_provinces as ip on ip.id = ad.province_id
                    where
                        ad.deleted_at is null
                        and ad.type IN ('dealer', 'sub_dealer')
                    group by
                        parent_id
                ) ar on ar.store_id = so.store_id
                and ar.model = so.model
                JOIN (
                    select
                        ps.id marketing_id,
                        ps.name marketing,
                        rl.name position
                    from
                        personels as ps
                        join positions as rl on rl.id = ps.position_id
                        and rl.deleted_at is null
                    where
                        ps.deleted_at is null
                ) as m on m.marketing_id = so.personel_id
            where
                sd.deleted_at is null
                and dc.id is null
                and YEAR(COALESCE(i.created_at, so.date)) = $year
                and month(COALESCE(i.created_at, so.date)) in ($month)
                and m.marketing_id = '$personelId'
                and pd.product_group_id = '$productId'
            group by
                pd.product_group_id,
                so.personel_id
            ORDER BY
                volume asc"));

        return $query;
    }

    public function distributorStatistic(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500);
            if ($request->year) {
                $now = date('Y-m-d', strtotime($request->year . "-12-31"));

                $datey = new Carbon($now);
                $last2year = $datey->subYear()->startOfYear();
                // dd($now);
            } else {
                $now = Carbon::now();
                $last2year = Carbon::now()->subYear();
            }

            $dealerv2filter = [];
            if ($request->sub_region_id) {
                $dealerv2filter = DealerMinimalis::select("id")->distributor()->whereHas('addressDetail', function ($query) use ($request) {
                    return $query->where("type", "dealer")->whereIn('district_id', $this->districtListByArea($request->sub_region_id));
                })->get()->map(function ($data, $key) {
                    return $data->id;
                });

                unset($request["region_id"]);
            } else if ($request->region_id) {
                $dealerv2filter = DealerMinimalis::select("id")->distributor()->whereHas('addressDetail', function ($query) use ($request) {
                    return $query->where("type", "dealer")->whereIn('district_id', $this->districtListByArea($request->region_id));
                })->get()->map(function ($data, $key) {
                    return $data->id;
                });
            }

            // return collect($dealerv2filter);

            // select hijau
            $from_distributor = $this->sales_order_minimalis->query()
                ->whereIn("status", ["confirmed", "return", "pending"])
                ->when($request->sub_region_id || $request->region_id, function ($QQQ) use ($request, $dealerv2filter) {
                    return $QQQ->whereIn("distributor_id", $dealerv2filter);
                })
                ->select(
                    DB::raw("(sum(total)) as from_distributor"),
                    DB::raw("(DATE_FORMAT(date, '%Y')) as year"),
                )
                ->where("type", 2)
                ->whereBetween('date', [$last2year, $now])
                ->groupBy(DB::raw("DATE_FORMAT(date, '%Y')"))->get();

            // return $from_distributor;

            $years_from_distributor = [
                "name" => "from_distributor",
            ];

            for ($i = 1; $i >= 0; $i--) {
                if ($request->year) {

                    $now = date('Y-m-d', strtotime($request->year . "-12-31"));

                    $datenow = new Carbon($now);
                    $years_from_distributor["data"][$datenow->subYears($i)->format("Y")] = 0;
                } else {

                    $years_from_distributor["data"][Carbon::now()->subYears($i)->format("Y")] = 0;
                }
            }

            $from_distributor_array = [];
            foreach ($from_distributor as $value) {
                $years_from_distributor["data"][$value->year] = $value->from_distributor ?: 0;
            }

            $to_distributor = $this->sales_order_minimalis->query()
                ->whereIn("status", ["confirmed", "return", "pending"])
                ->when($request->sub_region_id || $request->region_id, function ($QQQ) use ($request, $dealerv2filter) {
                    return $QQQ->whereIn("store_id", $dealerv2filter);
                })
                ->where("type", 1)
                ->select(
                    DB::raw("(DATE_FORMAT(invoices.created_at, '%Y')) as year"),
                    DB::raw("(sum(invoices.total)) as to_distributor")
                )

                ->Join('invoices', 'invoices.sales_order_id', '=', 'sales_orders.id')
                ->whereBetween('invoices.created_at', [$last2year, $now])
                ->groupBy(DB::raw("DATE_FORMAT(invoices.created_at, '%Y')"))->get();

            // return $to_distributor;

            $years_to_distributor = [
                "name" => "to_distributor",
            ];

            for ($i = 1; $i >= 0; $i--) {
                if ($request->year) {

                    $now = date('Y-m-d', strtotime($request->year . "-12-31"));

                    $datenow = new Carbon($now);
                    $years_from_distributor["data"][$datenow->subYears($i)->format("Y")] = 0;
                } else {

                    $years_from_distributor["data"][Carbon::now()->subYears($i)->format("Y")] = 0;
                }
            }

            foreach ($to_distributor as $value) {
                $years_to_distributor["data"][$value->year] = $value->to_distributor ?: 0;
            }

            $data = [
                $years_from_distributor,
                $years_to_distributor,
            ];

            return $this->response("00", "Statistic Distibutor 2 year", $data);
        } catch (\Throwable $th) {

            return $this->response("01", "failed to get List Statistic Distibutor 2 year", $th->getMessage());
        }
    }

    public function saleorOrderLast4Month(Request $request)
    {
        try {

            if ($request->has("sub_region_id")) {
                unset($request->region_id);
                $personels_id = $this->personelListByArea($request->sub_region_id);
            } elseif ($request->has("region_id")) {
                $personels_id = $this->personelListByArea($request->region_id);
                unset($request->sub_region_id);
            } else {
                $personels_id = $this->personelListByArea();
                $has_request = true;
            }

            if ($request->month) {

                $start_date = Carbon::createFromFormat('Y-m', date('Y') . '-' . $request->month)->subMonth(3)->startOfMonth();
                $end_date = Carbon::createFromFormat('Y-m', date('Y') . '-' . $request->month)->lastOfMonth();
            } else {
                $start_date = Carbon::now()->subMonth(3);
                $end_date = Carbon::now();
            }

            // buat query yang isinya sub region saja
            $subReqion = SubRegion::when($request->region, function ($QQQ) use ($request) {
                return $QQQ->where("region_id", $request->region);
            })
                ->when($request->sub_region, function ($QQQ) use ($request) {
                    return $QQQ->where("id", $request->sub_region);
                })->groupBy('name')->get();

            $detail = [];

            if ($request->has("sub_region_id")) {
            } else {

                $sales_order_last_4month = $this->sales_order->query()

                    ->with([
                        "personel" => function ($QQQ) {
                            return $QQQ
                                ->whereHas("areaMarketing")
                                ->with([
                                    "areaMarketing" => function ($Q) {
                                        return $Q->with([
                                            "subRegionWithRegion" => function ($Q) {
                                                return $Q->with([
                                                    "region",
                                                ]);
                                            },
                                        ]);
                                    },
                                ]);
                        },
                    ])
                // ->whereBetween('created_at', [$start_date, $end_date])
                    ->where("status", "confirmed")
                    ->whereIn("personel_id", $personels_id)
                    ->where(function ($parameter) use ($start_date, $end_date) {
                        return $parameter
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ->whereBetween('created_at', [$start_date, $end_date]);
                            });
                    })
                    ->orWhere(function ($parameter) use ($start_date, $end_date) {
                        return $parameter
                            ->where("type", "2")
                            ->whereBetween('date', [$start_date, $end_date]);
                    }) /* filter by region */
                    ->when($request->has("region_id"), function ($Q) use ($request) {
                        return $Q->region($request->region_id);
                    })

                /* filter by sub region */
                    ->when($request->has("sub_region_id"), function ($Q) use ($request) {
                        return $Q->subRegion($request->sub_region_id);
                    })
                    ->whereNull("deleted_at")
                    ->whereHas("personel")
                    ->get();

                $xxx = $sales_order_last_4month->groupBy([
                    function ($val) {
                        return $val->personel ? $val->personel->areaMarketing->subRegionWithRegion->region->id : null;
                    },
                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->translatedFormat('F');
                        } else {
                            return Carbon::parse($val->created_at)->translatedFormat('F');
                        }
                    },
                ]);

                // return $xxx;

                $period = CarbonPeriod::create($start_date, $end_date)->month();
                foreach ($xxx as $region => $values) {
                    foreach ($period as $date) {
                        foreach ($values as $month => $value) {
                            $detail[$region][$date->translatedFormat('F')]["type"] = null;
                            $detail[$region][$date->translatedFormat('F')]["total"] = 0;
                            $detail[$region][$date->translatedFormat('F')]["direct"] = 0;
                            $detail[$region][$date->translatedFormat('F')]["indirect"] = 0;
                            foreach ($value as $key => $ord) {

                                // $detail[$region][$date->translatedFormat('F')]["indirect"] = 0;
                                $detail[$region][$date->translatedFormat('F')]["region"] = $ord->personel ? $ord->personel->areaMarketing->subRegionWithRegion->region->name : null;
                            }
                        }
                    }
                }

                foreach ($xxx as $region => $values) {
                    foreach ($period as $date) {
                        foreach ($values as $month => $value) {
                            $total_direct = 0;
                            $total_amount = 0;
                            $total_indirect = 0;
                            foreach ($value as $key => $ord) {
                                $detail[$region][$month]["type"] = $ord->type;
                                if ($ord->type == "2") {

                                    $total_indirect += $ord->total;
                                } else {
                                    if ($ord->invoice) {
                                        // if ($ord->invoice->payment_status == "settle") {
                                        $total_direct += $ord->invoice->total;
                                        // }
                                    }
                                }
                                // $detail[$region][$date->translatedFormat('F')]["indirect"] = 0;
                                $detail[$region][$month]["region"] = $ord->personel ? $ord->personel->areaMarketing->subRegionWithRegion->region->name : null;
                            }

                            $detail[$region][$month]["direct"] = $total_direct;

                            $detail[$region][$month]["indirect"] = $total_indirect;

                            $detail[$region][$month]["total"] = $total_direct + $total_indirect;
                        }
                    }
                }
            }

            return $this->response('00', 'success, sales order last four month', $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get sales order last four month", $th->getMessage());
        }
    }

    public function analisysGroupLast4Month(Request $request)
    {

        try {
            ini_set('max_execution_time', 1500); //3 minutes

            // if ($request->has("sub_region_id")) {
            //     unset($request->region_id);
            // } elseif ($request->has("region_id")) {
            //     unset($request->sub_region_id);
            // }

            $start_date = Carbon::now()->subYear()->startOfYear()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->endOfMonth();
            $personel_id = auth()->user()->personel_id;

            if ($request->has("region_id") || auth()->user()->hasAnyRole(
                'Direktur Utama',
                'administrator',
                'Support Bagian Distributor',
                'Support Bagian Kegiatan',
                'Support Distributor',
                'Support Kegiatan',
                'Support Supervisor',
                'Marketing Support',
                'Distribution Channel (DC)'
            )) {
                $region_id_cek = $request->region_id ?: null;
            } else {
                $region_id = Personel::query()->where("id", $personel_id)->with([
                    "areaMarketing" => function ($Q) {
                        return $Q->with([
                            "subRegionWithRegion" => function ($Q) {
                                return $Q->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                ])->first();
                $region_id_cek = $region_id->areaMarketing->subRegionWithRegion->region->id;
            }

            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing" => function ($Q) {
                                return $Q->with([
                                    "subRegionWithRegion" => function ($Q) {
                                        return $Q->with([
                                            "region",
                                        ]);
                                    },
                                ]);
                            },
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with("areaDistrictDealer.subRegion");
                    },
                    "invoice",
                ])
                ->where("status", "confirmed")
            // ->whereHas("personel", function ($QQQ) {
            //     return $QQQ->whereHas("areaMarketing");
            // })

            /* filter region */
            // ->when($region_id_cek, function ($Q) use ($request, $region_id_cek) {
            //     return $Q->whereIn("personel_id", $this->personelListByArea($region_id_cek));
            // })
                ->when($request->has("sub_region_id"), function ($qqq) use ($request) {
                    return $qqq
                        ->whereIn("personel_id", $this->personelListByArea($request->sub_region_id));
                })
                ->when($request->scope_supervisor, function ($Q) use ($request) {
                    return $Q->supervisor();
                })
                ->where(function ($QQQ) use ($start_date) {
                    return $QQQ
                        ->where(function ($QQQ) use ($start_date) {
                            return $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($start_date) {
                                    return $QQQ->where("created_at", ">=", $start_date);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($start_date) {
                            return $QQQ->where("type", "2")
                                ->where("date", ">=", $start_date);
                        });
                })
            // ->when($request->has("region_id"), function ($Q) use ($request) {
            //     return $Q->region($request->region_id);
            // })
                ->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("dealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictDealer")->withTrashed();
                        })
                        ->orWhereHas("subDealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictSubDealer")->withTrashed();
                        });
                })
                ->get();

            /* this is map data with empty value */
            $all_sub_region = DB::table('marketing_area_sub_regions')->when(
                $region_id_cek,
                function ($qqq) use ($request, $region_id_cek) {
                    return $qqq->where("region_id", $region_id_cek);
                }
            )->get();

            $all_sub_region_grouped = $all_sub_region->groupBy([
                function ($val) {
                    return $val->id;
                },
            ]);

            $arraysubregion_id = [];
            $periodyear = CarbonPeriod::create($start_date, $end_date)->month();
            foreach ($all_sub_region_grouped as $subregion => $value) {
                $arraysubregion_id[] = $value[0]->name;
                $detail[$subregion]["subregion_id"] = $value[0]->id;
                $detail[$subregion]["subregion"] = $value[0]->name;
                $detail[$subregion]["count_marketing"] = 0;
                $detail[$subregion]["count_dealer"] = 0;
                $detail[$subregion]["count_dealer_active"] = 0;
                $detail[$subregion]["count_subdealer"] = 0;
                $detail[$subregion]["count_subdealer_active"] = 0;
                $detail[$subregion]["count_store_active"] = 0;
                $detail[$subregion]["count_store"] = 0;
                $detail[$subregion]["direct"] = 0;
                foreach ($periodyear as $date) {
                    $detail[$subregion]["year"][$date->translatedFormat('Y')] = 0;
                }
            }

            $start_date_month = Carbon::now()->subMonth(3)->startOfMonth();
            $periodmonth = CarbonPeriod::create($start_date_month, $end_date)->month();
            foreach ($all_sub_region_grouped as $subregion => $values) {
                foreach ($periodmonth as $date) {
                    $detail[$subregion]["month"][$date->translatedFormat('F')] = 0;
                }
            }

            /* Delivery data */

            $sales_orders_grouped_four_months = collect($sales_orders)
                ->filter(function ($data, $key) {
                    if ($data->type == "2") {
                        return $data->created_at >= Carbon::now()->subMonths(3)->startOfMonth();
                    } else {

                        return $data->invoice->created_at >= Carbon::now()->subMonths(3)->startOfMonth();
                    }
                })
                ->groupBy([
                    function ($val) {
                        if ($val->dealer) {
                            return $val->dealer->areaDistrictDealer->sub_region_id;
                        } else {
                            return $val->subDealer->areaDistrictSubDealer->sub_region_id;
                        }
                    },
                    function ($val) {
                        return $val->created_at->format('Y');
                    },
                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->translatedFormat('F');
                        } else {
                            return Carbon::parse($val->created_at)->translatedFormat('F');
                        }
                    },
                ]);

            foreach ($sales_orders_grouped_four_months as $sub_region => $values) {
                foreach ($values as $year => $yearvalue) {
                    foreach ($yearvalue as $month => $monthvalue) {
                        $total = 0;
                        $total_indirect = 0;
                        $total_direct = 0;
                        foreach ($monthvalue as $ord) {
                            if ($ord->type == "2") {
                                $total_indirect += $ord->total;
                            } else {
                                if ($ord->invoice) {
                                    $total_direct += $ord->invoice->total;
                                }
                            }
                            // $detail[$sub_region]["subregion"] = $ord->personel->areaMarketing->subRegionWithRegion->name;
                            // $detail[$sub_region]["subregion"] = $ord->subDealer->areaDistrictSubDealer->name;
                        }
                        $detail[$sub_region]["month"][$month] = $total_direct + $total_indirect;
                    }
                }
            }

            /** delivery data years */

            $sales_orders_grouped_years = collect($sales_orders)
                ->filter(function ($data, $key) {
                    if ($data->type == "2") {
                        return $data->created_at >= Carbon::now()->subYear()->startOfYear();
                    } else {

                        return $data->invoice->created_at >= Carbon::now()->subYear()->startOfYear();
                    }
                })
                ->groupBy([
                    function ($val) {
                        if ($val->dealer) {
                            return $val->dealer->areaDistrictDealer->sub_region_id;
                        } else {
                            return $val->subDealer->areaDistrictSubDealer->sub_region_id;
                        }
                    },
                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->format('Y');
                        } else {
                            return Carbon::parse($val->created_at)->format('Y');
                        }
                    },
                ]);

            foreach ($sales_orders_grouped_years as $sub_region => $values) {
                foreach ($values as $year => $yearvalues) {
                    $total = 0;
                    $total_indirect = 0;
                    $total_direct = 0;
                    foreach ($yearvalues as $ord) {
                        if ($ord->type == "2") {
                            $total_indirect += $ord->total;
                        } else {
                            if ($ord->invoice) {
                                $total_direct += $ord->invoice->total;
                            }
                        }
                    }
                    $detail[$sub_region]["indirect"] = $total_indirect;
                    $detail[$sub_region]["direct"] = $total_direct;

                    $detail[$sub_region]["year"][$year] = $total_direct + $total_indirect;
                }
            }

            /** kenaikan */
            /** (penjualan tahun ini - penjualan tahun lalu)/penjualan tahun lalu*100 */

            foreach ($all_sub_region_grouped as $subregion => $values) {
                $this_year = $detail[$subregion]["year"][Carbon::now()->format("Y")];
                $last_year = $detail[$subregion]["year"][Carbon::now()->subYear()->startOfYear()->format("Y")];
                if ($this_year == 0) {
                    $this_year_final = 1;
                } else {
                    $this_year_final = $this_year;
                }
                $detail[$subregion]["sales_increase_rupiah"] = $this_year - $last_year;
                $detail[$subregion]["sales_increase_percentage"] = (($this_year - $last_year) / $this_year_final) * 100;
            }

            /** count data , count marketing now, count store active, count all store*/

            /** dealers groupby sub region */

            $dealers = $this->dealer->query()
                ->whereHas("areaDistrictDealer")
                ->with("areaDistrictDealer.subRegion")
                ->withTrashed()
                ->get();

            $dealer_grouped = collect($dealers)->groupBy([
                function ($val) {
                    return $val->areaDistrictDealer->sub_region_id;
                },
            ]);

            foreach ($dealer_grouped as $sub_region => $sub_regio_value) {
                $detail[$sub_region]["count_dealer"] = count($sub_regio_value);
            }

            /** sub_dealers groupby sub region */

            $subdealers = $this->sub_dealer->query()
                ->whereHas("areaDistrictSubDealer")
                ->withTrashed()
                ->with("areaDistrictSubDealer.subRegion")
                ->get();

            $subdealer_grouped = collect($subdealers)->groupBy([
                function ($val) {
                    return $val->areaDistrictSubDealer->sub_region_id;
                },
            ]);

            foreach ($subdealer_grouped as $sub_region => $sub_region_value) {
                $detail[$sub_region]["count_subdealer"] = count($sub_region_value);
            }
            foreach ($all_sub_region_grouped as $subregion => $values) {
                $detail[$subregion]["count_store"] = $detail[$subregion]["count_subdealer"] + $detail[$subregion]["count_dealer"];
            }

            /** marketing now */

            $marketing = $this->personel->query()
                ->with([
                    "areaMarketing" => function ($Q) {
                        return $Q->with([
                            "subRegionWithRegion" => function ($Q) {
                                return $Q->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                ])
                ->whereHas("areaMarketing")
                ->get();

            $marketing_grouped = collect($marketing)->groupBy([
                function ($val) {
                    return $val->areaMarketing->subRegionWithRegion->id;
                },
            ]);

            foreach ($marketing_grouped as $sub_region => $value) {

                $detail[$sub_region]["count_marketing"] = count($value);
            }

            // all dealer active
            $active_dealer = $this->dealer->query()
                ->whereHas("areaDistrictDealer")
                ->whereHas("salesOrder", function ($q) {
                    return $q
                        ->where("status", "confirmed")
                        ->where(function ($q) {
                            return $q
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) {
                                    return $QQQ->where("created_at", ">", Carbon::now()->subDays(365));
                                });
                        })
                        ->orWhere(function ($q) {
                            return $q
                                ->where("type", "2")
                                ->where("date", ">", Carbon::now()->subDays(365));
                        });
                })
                ->with("areaDistrictDealer.subRegion")
                ->with([
                    "salesOrder.invoice",
                ])
                ->get();

            $dealer_active_grouped = collect($active_dealer)->groupBy([
                function ($val) {
                    return $val->areaDistrictDealer->sub_region_id;
                },
            ]);

            foreach ($dealer_active_grouped as $sub_region => $sub_regio_value) {
                $detail[$sub_region]["count_dealer_active"] = count($sub_regio_value);
            }

            /** all sub_dealer active */
            $active_subdealer = $this->sub_dealer->query()
                ->whereHas("areaDistrictSubDealer")
                ->whereHas("salesOrderSubDelaer", function ($q) {
                    return $q
                        ->where("status", "confirmed")
                        ->where(function ($q) {
                            return $q
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) {
                                    return $QQQ->where("created_at", ">", Carbon::now()->subDays(365));
                                });
                        })
                        ->orWhere(function ($q) {
                            return $q
                                ->where("type", "2")
                                ->where("date", ">", Carbon::now()->subDays(365));
                        });
                })
                ->with("areaDistrictSubDealer.subRegion")
                ->with([
                    "salesOrderSubDelaer.invoice",
                ])
                ->get();

            $subdealer_active_grouped = collect($active_subdealer)->groupBy([
                function ($val) {
                    return $val->areaDistrictSubDealer->sub_region_id;
                },
            ]);

            foreach ($subdealer_active_grouped as $sub_region => $sub_regio_value) {
                $detail[$sub_region]["count_subdealer_active"] = count($sub_regio_value);
            }

            foreach ($all_sub_region_grouped as $subregion => $values) {
                $detail[$subregion]["count_store_active"] = $detail[$subregion]["count_subdealer_active"] + $detail[$subregion]["count_dealer_active"];
            }
            $detail_collect = collect($detail)->whereIn("subregion", $arraysubregion_id);

            // Setup necessary information for LengthAwarePaginator
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // slice the current page items
            $currentItems = $detail_collect->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $paginator = new LengthAwarePaginator($currentItems, count($detail_collect), $pageLimit, $currentPage, ['path' => $path]);

            return $this->response("00", "Statistic Diagram Direct Sales", $paginator);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function analisysGroupLast4MonthFix(Request $request)
    {
        try {
            $start_date = Carbon::now()->subYear()->startOfYear()->startOfDay()->format("Y-m-d h:m:s");

            $end_date = Carbon::now()->endOfMonth();
            $personel_id = auth()->user()->personel_id;
            $this_year = Carbon::now()->format("Y");
            $sub_region = SubRegion::query()
                ->when(auth()->user()->hasAnyRole(is_all_data()), function ($QQQ) {
                    return $QQQ;
                })
                ->when(!auth()->user()->hasAnyRole(is_all_data()), function ($QQQ) {
                    return $QQQ
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->whereHas("region", function ($QQQ) {
                                    return $QQQ->where("personel_id", auth()->user()->personel_id);
                                })
                                ->orWhere("personel_id", auth()->user()->personel_id);
                        });
                })
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    return $QQQ->where("region_id", $request->region_id);
                })

            /* ersonel branch */
                ->when($request->personel_branch, function ($QQQ) use ($request) {
                    return $QQQ->personelBranch($request->personel_id ? $request->personel_id : auth()->user()->personel_id);
                })

                ->orderBy('name')
                ->paginate($request->limit ? $request->limit : 5);

            $district_id = DB::table('marketing_area_districts')
                ->whereIn('sub_region_id', $sub_region->pluck('id'))
                ->pluck('district_id');

            $store_list = DB::table('address_with_details')
                ->whereIn('address_with_details.district_id', $district_id)
                ->whereIn('type', ['dealer', 'sub_dealer'])
                ->join('marketing_area_districts', 'marketing_area_districts.district_id', 'address_with_details.district_id');

            $sales_order_temp = collect();

            $sales_orders = DB::table('sales_orders as s')
                ->leftJoin("invoices as i", "i.sales_order_id", "=", "s.id")
                ->leftJoin("view_store_region as vsr", "vsr.store_id", "s.store_id")
                ->whereIn('s.store_id', $store_list->pluck('parent_id'))
                ->whereNull("s.deleted_at")
                ->whereNull("i.deleted_at")
                ->where(function ($sales_order_diff) use ($start_date) {
                    return $sales_order_diff
                        ->where(function ($sales_order_direct_migrate) use ($start_date) {
                            return $sales_order_direct_migrate
                                ->whereNotNull('s.note')
                                ->whereNotNull('i.id')
                                ->whereBetween('i.created_at', [$start_date, now()]);
                        })
                        ->orWhere(function ($sales_order_direct) use ($start_date) {
                            return $sales_order_direct
                                ->whereNull('s.note')
                                ->whereNotNull('i.id')
                                ->whereBetween('i.created_at', [$start_date, now()]);
                        })
                        ->orWhere(function ($sales_order_indirect_migrate) use ($start_date) {
                            return $sales_order_indirect_migrate
                                ->whereNotNull('s.note')
                                ->whereNull('i.id')
                                ->whereBetween('s.date', [$start_date, now()]);
                        })
                        ->orWhere(function ($sales_order_indirect) use ($start_date) {
                            return $sales_order_indirect
                                ->whereNull('s.note')
                                ->whereNull('i.id')
                                ->whereBetween('s.date', [$start_date, now()]);
                        });
                })
                ->whereIn("s.status", ["confirmed", "returned", "pending"])
                ->select(['s.store_id', DB::RAW('SUM(s.total) as total'), DB::raw("if(s.type = 2, s.date, i.created_at) as created_at"), DB::raw("if(s.type = 2, year(s.date), year(i.created_at)) as year_order"), "vsr.sub_region", "vsr.sub_region_id"])
                ->groupBy('s.store_id')
                ->get();

            $store_list_with_district = $store_list->get()
                ->map(function ($query) use ($sales_orders) {
                    $sales = collect($sales_orders)
                        ->whereBetween("created_at", [now()->startOfYear()->startOfDay()->format("Y-m-d h:m:s"), now()->format("Y-m-d h:m:s")])
                        ->where('store_id', $query->parent_id)->first();
                    $query->total = ($sales ? $sales->total : 0);
                    return $query;
                })
                ->where('total', '!=', 0);

            $start_date_month = Carbon::now()->subMonth(3)->startOfMonth();
            $area_list = $store_list->get();
            $sales_order_four_month = $sales_orders->where("created_at", ">=", $start_date_month);

            /**
             * four months template
             */
            $four_months = [];
            for ($i = 3; $i > 0; $i--) {
                $four_months[now()->startOfMonth()->subMonth($i)->translatedFormat('F')] = 0;
            }

            $four_months[now()->translatedFormat('F')] = 0;

            /**
             * four months grouped
             */
            $sales_order_four_month_grouped = $sales_order_four_month
                ->map(function ($order) use ($area_list) {
                    $sub_region = $area_list->where("parent_id", $order->store_id)->first();
                    $order->sub_region_id = $sub_region->sub_region_id;
                    return $order;
                })
                ->sortBy("created_at")
                ->groupBy([
                    function ($val) {
                        return $val->sub_region_id;
                    },
                    function ($val) {
                        return Carbon::parse($val->created_at)->translatedFormat('F');
                    },
                ])

                ->map(function ($order_sub, $sub_region_id) {
                    $order_sub = collect($order_sub)->map(function ($order, $month) {
                        return collect($order)->sum("total");
                    });
                    return $order_sub;
                })
                ->map(function ($order, $sub_region_id) use ($four_months) {

                    return collect($four_months)->merge($order);
                });

            /**
             * four month order grouped
             */
            $period_month = CarbonPeriod::create($start_date_month, $end_date)->month();

            $count_store_by_sub_region = $store_list
                ->select('sub_region_id', DB::raw('count(*) as total'))
                ->groupBy('sub_region_id')
                ->get();

            /**
             * marketing list
             */
            $marketing_grouped = $this->personel->query()
                ->with([
                    "areaMarketing" => function ($Q) {
                        return $Q->with([
                            "subRegionWithRegion",
                        ]);
                    },
                ])
                ->whereHas("areaMarketing")
                ->get()
                ->groupBy(function ($val) {
                    return $val->areaMarketing->subRegionWithRegion->id;
                })
                ->map(function ($marketing, $personel_id) {
                    return $marketing->count();
                });

            /**
             * active store
             */
            $active_store = $sales_orders
                ->map(function ($order) use ($area_list) {
                    $sub_region = $area_list->where("parent_id", $order->store_id)->first();
                    $order->sub_region_id = $sub_region->sub_region_id;
                    return $order;
                })
                ->unique("store_id")
                ->groupBy([
                    function ($val) {
                        return $val->sub_region_id;
                    },
                ])
                ->map(function ($order_sub, $sub_region_id) {
                    return collect($order_sub)->count();
                });

            $refactor_sub_region = collect($sub_region)->toArray();

            $refactor_sub_region['data'] = collect($refactor_sub_region['data'])
                ->map(function ($query) use ($store_list_with_district, $count_store_by_sub_region, $sales_order_four_month_grouped, $period_month, $marketing_grouped, $active_store, $sales_orders) {
                    $query = (object) $query;

                    $sales = collect($store_list_with_district)
                        ->where('sub_region_id', $query->id);

                    $store = collect($count_store_by_sub_region)->where('sub_region_id', $query->id)->first();

                    /* last year recap */
                    $last_year_recap = $sales_orders->where("sub_region_id", $query->id)->where("year_order", now()->subYear()->format("Y"))->sum("total");

                    /* count marketing on sub region */
                    $marketing_sub_region = $marketing_grouped
                        ->filter(function ($marketing, $sub) use ($query) {
                            if ($sub == $query->id) {
                                return $marketing;
                            }
                        })
                        ->first();

                    $sales_year = [
                        now()->subYear()->format("Y") => $last_year_recap,
                        now()->format("Y") => ($sales ? $sales->sum("total") : 0),
                    ];

                    $query->subregion_id = $query->id;
                    $query->subregion = $query->name;
                    $query->count_marketing = $marketing_sub_region ? $marketing_sub_region : 0;
                    $query->total = $sales_orders->where("sub_region_id", $query->id)->sum("total");
                    $query->year = $sales_year;

                    $query->sales_increase_rupiah = $sales_year[now()->format("Y")] - $sales_year[now()->subYear()->format("Y")];
                    $query->sales_increase_percentage = $sales_year[now()->format("Y")] > 0 ? (($sales_year[now()->format("Y")] - $sales_year[now()->subYear()->format("Y")]) / $sales_year[now()->format("Y")] * 100) : 0;

                    $query->count_store = ($store ? $store->total : 0);

                    /* active_store */
                    $active_store_sub = $active_store
                        ->filter(function ($active_store, $sub) use ($query) {
                            if ($sub == $query->id) {
                                return $active_store;
                            }
                        })
                        ->first();

                    $query->count_store_active = ($active_store_sub ? $active_store_sub : 0);

                    $sales_order_four_month_sub_region = $sales_order_four_month_grouped
                        ->filter(function ($month_of_sub, $sub_region_id) use ($query) {
                            if ($sub_region_id == $query->id) {
                                return $month_of_sub;
                            }
                        })
                        ->first();

                    if ($sales_order_four_month_sub_region) {
                        $query->month = $sales_order_four_month_sub_region;
                    } else {
                        foreach ($period_month as $date) {
                            $query->month[$date->translatedFormat('F')] = 0;
                        }
                    }

                    return $query;
                });

            // if ($request->sort_by == 'oktober') {
            //     if ($request->direction == "desc") {
            //         // dd("sas");
            //         $sortedResult =
            //             collect($refactor_sub_region["data"])->sortByDesc(function ($item) {
            //                 // dd($item->month["Oktober"]);
            //                 return $item->month["Oktober"];
            //             })->values();
            //     } elseif ($request->direction == "asc") {
            //         $sortedResult = $object->getCollection()->sortBy(function ($item) {
            //             return $item->month?->september;
            //         })->values();
            //     }

            //     collect($refactor_sub_region["data"])->setCollection($sortedResult);
            // }
            // return $refactor_sub_region;

            return $this->response('00', 'success, sales order last four month', $refactor_sub_region);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    public function analisysGroupStore5yearVer2(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "sub_region_id" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "sub_region_id is required", $validator->errors());
            }

            ini_set('max_execution_time', 1500); //3 minutes
            $start_date = Carbon::now()->subYears(4)->startOfYear()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->endOfMonth();
            $is_distributor = $request->is_distributor === true ? true : false;

            $sub_dealers = SubDealerV2::query()->select("id", "name", "prefix", "sufix", "address", "personel_id", "status_color", "owner", "grading_id", "sub_dealer_id as store_id", "sub_dealer_id", DB::raw("if(sub_dealer_id, 'sub_dealer', 'sub_dealer') as store_type"))
                ->with([
                    "salesOrders" => function ($QQQ) use ($start_date, $end_date, $request) {
                        return $QQQ
                            ->with([
                                "invoiceOnly",
                                "personel",
                                "sales_order_detail",
                            ])
                            ->where("status", "confirmed")
                            ->when($request->has("type"), function ($q) use ($request) {
                                return $q->whereIn("type", $request->type);
                            })
                            ->where(function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ
                                    ->where(function ($QQQ) use ($start_date, $end_date) {
                                        return $QQQ
                                            ->where("type", "1")
                                            ->whereHas("invoiceOnly", function ($QQQ) use ($start_date, $end_date) {
                                                return $QQQ->whereYear("created_at", ">", $start_date);
                                            });
                                    })
                                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                                        return $QQQ
                                            ->where("type", "2")
                                            ->whereYear("date", ">", $start_date);
                                    });
                            });
                    },
                    "personel",
                    "grading",
                    "areaDistrictStore.subRegion",
                    "ditributorContract",
                ])
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->subRegion($request->sub_region_id)->withTrashed();
                })
                ->when($request->has("type"), function ($q) use ($request) {
                    $q->whereHas("salesOrders", function ($Q) use ($request) {
                        return $Q->whereIn("type", $request->type);
                    });
                });

            $dealers = DealerV2::query()->select("id", "name", "prefix", "sufix", "address", "personel_id", "status_color", "owner", "grading_id", "dealer_id as store_id", "dealer_id", DB::raw("if(dealer_id, 'dealer', 'dealer') as store_type"))
                ->with([
                    "salesOrders" => function ($QQQ) use ($start_date, $end_date, $request) {
                        return $QQQ
                            ->with([
                                "invoiceOnly",
                                "personel",
                                "sales_order_detail",
                            ])
                            ->when($request->has("type"), function ($q) use ($request) {
                                return $q->whereIn("type", $request->type);
                            })
                            ->where(function ($QQQ) use ($start_date, $end_date) {
                                return $QQQ
                                    ->where(function ($QQQ) use ($start_date, $end_date) {
                                        return $QQQ
                                            ->where("type", "1")
                                            ->whereHas("invoiceOnly", function ($QQQ) use ($start_date, $end_date) {
                                                return $QQQ->whereYear("created_at", ">", $start_date);
                                            });
                                    })
                                    ->orWhere(function ($QQQ) use ($start_date, $end_date) {
                                        return $QQQ
                                            ->where("type", "2")
                                            ->whereYear("date", ">", $start_date);
                                    });
                            });
                    },
                    "personel",
                    "grading",
                    "areaDistrictStore.subRegion",
                    "ditributorContract",
                ])

                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->subRegion($request->sub_region_id)->withTrashed();
                })
                ->when($request->has("type"), function ($q) use ($request) {
                    if (in_array(1, $request->type) && count($request->type) == 1) {
                        return $q->where("is_distributor", $request->type);
                    }
                    $q->whereHas("salesOrders", function ($Q) use ($request) {
                        return $Q->whereIn("type", $request->type);
                    });
                })
                ->union($sub_dealers)
                ->get();

            $detail = [];
            foreach ($dealers as $dealer) {
                $five_year_sales = [];
                for ($i = 4; $i >= 0; $i--) {
                    $five_year_sales[Carbon::now()->subYears($i)->format("Y")] = 0;
                }
                collect($dealer->ditributorContract)->map(function ($contract) use ($dealer, $five_year_sales) {
                    $sales_order_inside_contract = collect($dealer->salesOrders)
                        ->where("type", 1)
                        ->whereBetween("invoiceOnly.created_at", [$contract->contract_start, $contract->contract_end]);

                    $sales_order_inside_contract_indirect = collect($dealer->salesOrders)
                        ->where("type", 2)
                        ->whereBetween("created_at", [$contract->contract_start, $contract->contract_end]);

                    $dealer->sales_order_inside_contract = $sales_order_inside_contract;
                    $dealer->sales_order_inside_contract_indirect = $sales_order_inside_contract_indirect;

                    return $contract;
                });

                $sales_grouped = collect($dealer->salesOrders)->whereIn("status", ["confirmed"])->groupBy([
                    function ($val) {
                        if ($val->invoiceOnly) {
                            return $val->invoiceOnly->created_at->format('Y');
                        } else {
                            return Carbon::parse($val->created_at)->format('Y');
                        }
                    },
                ]);

                $last_transaction = null;
                $last_direct = null;
                $last_indirect = null;
                $cek_last_direct = null;
                $personel = null;
                $total_transaction = 0;
                // $five_year_sales[$year] = 0;
                collect($sales_grouped)->map(function ($order_year, $year) use (&$five_year_sales, $dealer, &$last_order, &$last_transaction, &$last_direct, &$last_indirect, &$cek_last_direct, &$personel, &$total_transaction) {
                    $last_direct = collect($order_year)
                        ->sortByDesc(function ($date_confirmed) {
                            if ($date_confirmed->type == "1") {
                                return $date_confirmed->invoiceOnly->created_at;
                            }
                        })
                        ->first()->invoiceOnly;

                    $cek_last_direct = !empty($last_direct) ? $last_direct->created_at : null;

                    $personel = collect($order_year)->first()->personel;
                    $total_transaction = collect($order_year)->count();

                    $last_indirect = collect($order_year)
                        ->sortByDesc(function ($date_confirmed) {
                            if ($date_confirmed->type == "2") {
                                return $date_confirmed->created_at;
                            }
                        })
                        ->first()["created_at"];

                    if ($cek_last_direct && $last_indirect) {
                        if (Carbon::createFromFormat("Y-m-d H:i:s", $last_indirect)->gt(Carbon::createFromFormat("Y-m-d H:i:s", $cek_last_direct))) {
                            $last_transaction = $last_indirect;
                        } else {
                            $last_transaction = $cek_last_direct;
                        }
                    } else if ($cek_last_direct) {
                        $last_transaction = $cek_last_direct;
                    } else if ($last_indirect) {
                        $last_transaction = $last_indirect;
                    }

                    $total_direct = collect($order_year)->where("type", "1")->sum("invoiceOnly.total");
                    $total_indirect = collect($order_year)->where("type", "2")->sum("total");
                    $five_year_sales[$year] = $total_direct + $total_indirect;
                    return $five_year_sales;
                });

                if (Carbon::parse($dealer->last_order)->format('Y-m-d') >= Carbon::now()->subDays(365)) {
                    $status_dealer = 'active';
                } else {
                    $status_dealer = 'not_active';
                }

                // $dealer->unsetRelation("areaDistrictStore");

                if ($is_distributor == true) {
                    if ((!empty($dealer->sales_order_inside_contract) && count($dealer->sales_order_inside_contract) > 0) || (!empty($dealer->sales_order_inside_contract_indirect) && count($dealer->sales_order_inside_contract_indirect) > 0)) {
                        $detail[$dealer->id]["status_dealer"] = $status_dealer;
                        $detail[$dealer->id]["transaction_total"] = $total_transaction;
                        // $detail[$dealer->id]["sales_grouped"] = $sales_grouped;
                        $detail[$dealer->id]["last_transaction"] = $last_transaction;
                        $detail[$dealer->id]["marketing"] = $dealer->personel;
                        // $detail[$dealer->id]["last_direct"] = $last_direct;
                        $detail[$dealer->id]["last_indirect"] = $last_indirect;
                        $detail[$dealer->id]["last_direct"] = $cek_last_direct;
                        $detail[$dealer->id]["store_type"] = $dealer->store_type;

                        $detail[$dealer->id]["store"] = $dealer;
                        if ($dealer->store_type == 'dealer') {
                            $detail[$dealer->id]["store"]["prefix_id"] = config("app.dealer_id_prefix");
                        } else {
                            $detail[$dealer->id]["store"]["prefix_sub_id"] = config("app.sub_dealer_id_prefix");
                        }
                        $detail[$dealer->id]['years'] = $five_year_sales;
                    }
                } else {
                    $detail[$dealer->id]["status_dealer"] = $status_dealer;
                    $detail[$dealer->id]["transaction_total"] = $total_transaction;
                    $detail[$dealer->id]["last_transaction"] = $last_transaction;
                    $detail[$dealer->id]["marketing"] = $dealer->personel;
                    $detail[$dealer->id]["last_indirect"] = $last_indirect;
                    $detail[$dealer->id]["last_direct"] = $cek_last_direct;
                    $detail[$dealer->id]['years'] = $five_year_sales;
                    $detail[$dealer->id]["store"] = $dealer;
                    $detail[$dealer->id]["store_type"] = $dealer->store_type;
                    if ($dealer->store_type == 'dealer') {
                        $detail[$dealer->id]["store"]["prefix_id"] = config("app.dealer_id_prefix");
                    } else {
                        $detail[$dealer->id]["store"]["prefix_sub_id"] = config("app.sub_dealer_id_prefix");
                        // $detail[$dealer->id]["store"]["area_district_store"] = $dealer->areaDistrictStore;
                    }
                }
            }

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // slice the current page items
            $currentItems = collect($detail)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $paginator = new LengthAwarePaginator($currentItems, count($detail), $pageLimit, $currentPage, ['path' => $path]);

            return $this->response("00", "Success get analisys group by subregion", $paginator);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function analisysGroupStore5year(Request $request)
    {
        try {
            // dd($request);
            $validator = Validator::make($request->all(), [
                //"sub_region_id" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "sub_region_id is required", $validator->errors());
            }

            ini_set('max_execution_time', 1500); //3 minutes
            if ($request->year) {
                // $date_start = date('Y-m-d', strtotime($request->year));
                $date_request = Carbon::createFromFormat("Y-m-d", $request->year . '-' . '12' . '-' . '31')->format('Y-m-d H:i:s');
                $datey = new Carbon($date_request);
                $start_date = $datey->subYear(4)->startOfYear()->format('Y-m-d H:i:s');
                $end_date = $date_request;
            } else {
                $start_date = Carbon::now()->subYears(4)->startOfYear()->format('Y-m-d H:i:s');
                $end_date = Carbon::now()->endOfMonth();
            }
            $is_distributor = $request->is_distributor == 'true' ? true : false;
            $sales_orders = $this->sales_order->query()
                ->with([
                    'personel.areaMarketing',
                    'dealer.areaDistrictDealer.subRegion',
                    'subdealer.areaDistrictDealer.subRegion',
                    'invoice',
                ])
                ->where("status", "confirmed")
                ->when($request->has("order_type"), function ($query) use ($request) {
                    return $query->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4]);
                })

                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })

                ->when($request->has("marketing_name"), function ($query) use ($request) {
                    return $query->whereHas("personel", function ($query) use ($request) {
                        return $query->where("name", "like", "%" . $request->marketing_name . "%");
                    });
                })

                ->where(function ($QQQ) use ($start_date, $request, $end_date) {

                    return $QQQ->where(function ($Q) use ($start_date, $request, $end_date) {
                        return $Q
                            ->where(function ($Q) use ($start_date, $request, $end_date) {
                                return $Q
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($Q) use ($start_date, $request, $end_date) {
                                        return $Q
                                            ->whereBetween('created_at', [$start_date, $end_date]);
                                    });
                            })
                            ->orWhere(function ($Q) use ($start_date, $request, $end_date) {
                                return $Q
                                    ->where("type", "2")
                                    ->whereBetween('created_at', [$start_date, $end_date]);
                            });
                    });
                })
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($Q) use ($request, $is_distributor) {
                        $Q->whereHas("dealer", function ($Q) use ($request, $is_distributor) {
                            $Q->when($is_distributor, function ($q) use ($request) {
                                return $q->where("is_distributor", 1)->whereHas("distributorContract");
                            })->subRegion($request->sub_region_id)->withTrashed();
                        })
                            ->orWhereHas("subDealer", function ($Q) use ($request) {
                                return $Q
                                    ->subRegion($request->sub_region_id)
                                    ->withTrashed();
                            });
                    });
                })

            /* filter by sub region ids */
                ->when($request->region_ids || $request->sub_region_ids, function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($Q) use ($request, $is_distributor) {
                        $Q->whereHas("dealer", function ($Q) use ($request, $is_distributor) {
                            $Q->when($is_distributor, function ($q) use ($request) {
                                return $q->where("is_distributor", 1)->whereHas("distributorContract");
                            })
                                ->when($request->region_ids, function ($query) use ($request) {
                                    return $query->subRegionArray($request->region_ids)->withTrashed();
                                })
                                ->when($request->sub_region_ids, function ($query) use ($request) {
                                    return $query->subRegionArray($request->sub_region_ids)->withTrashed();
                                });
                        })
                            ->orWhereHas("subDealer", function ($Q) use ($request) {
                                return $Q->when($request->region_ids, function ($query) use ($request) {
                                    return $query->subRegionArray($request->region_ids)->withTrashed();
                                })
                                    ->when($request->sub_region_ids, function ($query) use ($request) {
                                        return $query->subRegionArray($request->sub_region_ids)->withTrashed();
                                    });
                            });
                    });
                })

            /* filter by sub region */
                ->when($request->sub_region, function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($Q) use ($request, $is_distributor) {
                        $Q->whereHas("dealer", function ($Q) use ($request, $is_distributor) {
                            $Q->when($is_distributor, function ($q) use ($request) {
                                return $q->where("is_distributor", 1)->whereHas("distributorContract");
                            })->subRegionName($request->sub_region)->withTrashed();
                        })
                            ->orWhereHas("subDealer", function ($Q) use ($request) {
                                return $Q->subRegionName($request->sub_region)->withTrashed();
                            });
                    });
                })

                ->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("dealer", function ($Q) use ($request) {
                            return $Q->withTrashed();
                        })
                        ->orWhereHas("subDealer", function ($Q) use ($request) {
                            return $Q->withTrashed();
                        });
                })
                ->get();

            $detail = [];

            $sales_orders_grouped_five_years = collect($sales_orders)
                ->groupBy([
                    function ($val) {
                        if ($val->model == "1") {
                            return $val->store_id;
                        } else {
                            return $val->subDealer->id;
                        }
                    },
                    function ($val) {
                        return confirmation_time($val)->format('Y');
                    },
                ]);

            $periodyear = CarbonPeriod::create($start_date, $end_date)->month();
            foreach ($sales_orders_grouped_five_years as $store_id => $value) {
                $detail[$store_id]["last_indirect"] = null;
                $detail[$store_id]["last_direct"] = null;

                $detail[$store_id]["store"] = null;
                $detail[$store_id]["last_transaction"] = null;
                $detail[$store_id]["status_dealer"] = null;
                $detail[$store_id]["marketing"] = null;
                $detail[$store_id]["transaction_total"] = 0;
                foreach ($periodyear as $year => $date) {

                    $detail[$store_id]["years"][$date->translatedFormat('Y')] = 0;
                }
            }

            foreach ($sales_orders_grouped_five_years as $store_id => $values) {
                $total_transaction = 0;

                foreach ($values as $year => $yearvalue) {
                    $total = 0;
                    $total_indirect = 0;
                    $total_direct = 0;
                    foreach ($yearvalue as $ord) {

                        if ($ord->type == "2") {
                            $total_indirect += $ord->total;
                            if ($ord->created_at > Carbon::now()->subDays(365)) {
                                $detail[$store_id]["status_dealer"] = "active";
                            } else {
                                $detail[$store_id]["status_dealer"] = "not_active";
                            }
                        } else {
                            if ($ord->invoice) {
                                if ($ord->invoice->created_at > Carbon::now()->subDays(365)) {
                                    $detail[$store_id]["status_dealer"] = "active";
                                } else {
                                    $detail[$store_id]["status_dealer"] = "not_active";
                                }
                                $total_direct += $ord->invoice->total;
                            }
                        }

                        $detail[$store_id]["marketing"] = $ord->personel;

                        if ($ord->dealer) {
                            $detail[$store_id]["store"] = $ord->dealer;
                        }

                        if ($ord->subdealer) {
                            $detail[$store_id]["store"] = $ord->subdealer;
                        }
                        $detail[$store_id]["direct"] = $total_direct;
                        $detail[$store_id]["indirect"] = $total_indirect;
                    }

                    $detail[$store_id]["years"][$year] = $total_direct + $total_indirect;
                    $total_transaction += count($yearvalue);
                }
                $detail[$store_id]["transaction_total"] = $total_transaction;
            }

            $indirect = $sales_orders->where("type", "2")->sortByDesc("date")->groupBy([
                function ($val) {
                    return $val->dealer ? $val->dealer->id : $val->subDealer->id;
                },
                function ($val) {
                    return Carbon::parse($val->created_at)->format('Y');
                },
            ]);

            $direct = $sales_orders->where("type", "1")->whereNotNull("invoice")->sortByDesc("invoice.created_at")->groupBy([
                function ($val) {
                    return $val->dealer ? $val->dealer->id : $val->subDealer->id;
                },
                function ($val) {
                    if ($val->invoice) {
                        return $val->invoice->created_at->format('Y');
                    }
                },
            ]);

            foreach ($indirect as $store_id_ind => $values) {
                foreach ($values as $year => $yearvalue) {
                    foreach ($yearvalue as $ord) {
                        $detail[$store_id_ind]["last_indirect"] = $ord->created_at;
                    }
                }
            }

            foreach ($direct as $store_id => $values) {
                foreach ($values as $year => $yearvalue) {
                    foreach ($yearvalue as $ord) {
                        $detail[$store_id]["last_direct"] = $ord->invoice->created_at;
                    }
                }
            }

            foreach ($sales_orders_grouped_five_years as $store_id => $values) {
                if ($detail[$store_id]["last_direct"] && $detail[$store_id]["last_indirect"]) {
                    if (Carbon::createFromFormat("Y-m-d H:i:s", $detail[$store_id]["last_indirect"])->gt(Carbon::createFromFormat("Y-m-d H:i:s", $detail[$store_id]["last_direct"]))) {
                        $detail[$store_id]["last_transaction"] = $detail[$store_id]["last_indirect"];
                    } else {
                        $detail[$store_id]["last_transaction"] = $detail[$store_id]["last_direct"];
                    }
                } else if ($detail[$store_id]["last_direct"]) {
                    $detail[$store_id]["last_transaction"] = $detail[$store_id]["last_direct"];
                } elseif ($detail[$store_id]["last_indirect"]) {
                    $detail[$store_id]["last_transaction"] = $detail[$store_id]["last_indirect"];
                }
            }

            // Setup necessary information for LengthAwarePaginator
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // slice the current page items
            $currentItems = collect($detail)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $paginator = new LengthAwarePaginator($currentItems, count($detail), $pageLimit, $currentPage, ['path' => $path]);

            $yeard = [];

            foreach ($periodyear as $year) {
                $yeard[] = $year->translatedFormat('Y');
            }

            if ($request->sort_by && in_array($request->sort_by, array_values(array_unique($yeard)))) {
                if ($request->direction == "desc") {
                    $year = strval($request->sort_by);
                    $sortedResult = $paginator->getCollection()->sortByDesc(function ($item) use ($year) {
                        return $item["years"][$year];
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $paginator->getCollection()->sortBy(function ($item) use ($year) {
                        return $item["years"][$year];
                    })->values();
                }

                $paginator->setCollection($sortedResult);
            }

            return $this->response("00", "Success get analisys group by subregion", $paginator);
        } catch (\Throwable $th) {
            return $this->response("01", "Failed get analisys group by subregion", $th->getMessage());
        }
    }

    public function analisysGroupStore5YearDetail(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "sub_region_id" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "sub_region_id is required", $validator->errors());
            }

            $is_distributor = $request->is_distributor === true ? true : false;

            $start_date = Carbon::now()->subYears(4)->startOfYear()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->endOfMonth();

            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with("areaDistrictDealer.subRegion");
                    },
                    "invoice",
                ])
                ->where("status", "confirmed")

            /* filter subregion */
            // ->when($request->has("sub_region_id"), function ($qqq) use ($request) {
            //     return $qqq
            //         ->whereIn("personel_id", $this->personelListByArea($request->sub_region_id));
            // })

                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })

                ->where(function ($QQQ) use ($start_date, $request) {

                    return $QQQ->where(function ($Q) use ($start_date, $request) {
                        return $Q
                            ->where(function ($Q) use ($start_date, $request) {
                                return $Q
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($Q) use ($start_date, $request) {
                                        return $Q
                                            ->where("created_at", ">", $start_date);
                                    });
                            })
                            ->orWhere(function ($Q) use ($start_date, $request) {
                                return $Q
                                    ->where("type", "2")
                                    ->where("date", ">", $start_date);
                            });
                    });
                })

                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($Q) use ($request, $is_distributor) {
                        $Q->whereHas("dealer", function ($Q) use ($request, $is_distributor) {
                            return $Q->when($is_distributor == true, function ($q) use ($request) {
                                return $q->where("is_distributor", "1")->whereHas("distributorContract");
                            })->subRegion($request->sub_region_id)->withTrashed();
                        })
                            ->orWhereHas("subDealer", function ($Q) use ($request) {
                                return $Q->subRegion($request->sub_region_id)->withTrashed();
                            });
                    });
                })
                ->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("dealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictDealer")->withTrashed();
                        })
                        ->orWhereHas("subDealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictSubDealer")->withTrashed();
                        });
                })
                ->get();
            $detail = [];

            $sales_orders_grouped_five_years = collect($sales_orders)
                ->groupBy([
                    function ($val) {
                        if ($val->dealer) {
                            return $val->dealer->areaDistrictDealer->sub_region_id;
                        } else {
                            return $val->subDealer->areaDistrictSubDealer->sub_region_id;
                        }
                    },
                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->format('Y');
                        } else {
                            return Carbon::parse($val->created_at)->format('Y');
                        }
                    },
                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->translatedFormat('F');
                        } else {
                            return Carbon::parse($val->created_at)->translatedFormat('F');
                        }
                    },
                ]);

            $month = ['Januari' => 0, 'Februari' => 0, 'Maret' => 0, 'April' => 0, 'Mei' => 0, 'Juni' => 0, 'Juli' => 0, 'Agustus' => 0, 'September' => 0, 'Oktober' => 0, 'November' => 0, 'Desember' => 0];

            $periodyear = CarbonPeriod::create($start_date, $end_date);

            $marketing_area_sub_region = SubRegion::findOrFail($request->sub_region_id);

            // return $marketing_area_sub_region;

            // foreach ($marketing_area_sub_region as $sub_region_id => $valueyears) {
            foreach ($periodyear as $years => $valuemonth) {
                $detail[$valuemonth->format('Y')] = $month;
                $detail[$valuemonth->format('Y')]["sub_region_id"] = $marketing_area_sub_region->name;
                $detail[$valuemonth->format('Y')]["transaction_total"] = 0;
            }
            // }

            // return $detail;

            foreach ($sales_orders_grouped_five_years as $sub_region_id => $values) {
                foreach ($values as $years => $yearsvalue) {

                    // $total = 0;
                    foreach ($yearsvalue as $month => $monthvalue) {
                        $total = 0;
                        $total_indirect = 0;
                        $total_direct = 0;
                        foreach ($monthvalue as $ord) {
                            if ($ord->type == "2") {
                                $total_indirect += $ord->total;
                            } else {
                                if ($ord->invoice) {
                                    $total_direct += $ord->invoice->total;
                                }
                            }
                            // $detail[$sub_region]["subregion"] = $ord->personel->areaMarketing->subRegionWithRegion->name;
                            // $detail[$sub_region]["subregion"] = $ord->subDealer->areaDistrictSubDealer->name;
                        }
                        $detail[$years][$month] = $total_direct + $total_indirect;
                        $detail[$years]["transaction_total"] = 0;
                    }
                }
            }

            foreach ($sales_orders_grouped_five_years as $sub_region_id => $values) {
                foreach ($values as $years => $yearsvalue) {
                    $total = 0;
                    foreach ($yearsvalue as $month => $monthvalue) {
                        $total += $detail[$years][$month];
                    }
                    $detail[$years]["transaction_total"] = $total;
                }
            }

            return $this->response("00", "Success get analisys group by subregion detail 5 years", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "Failed get analisys group by subregion detail 5 years", $th->getMessage());
        }
    }

    public function analisysGroupSubRegionMarketing1Year(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes

            $validator = Validator::make($request->all(), [
                "sub_region_id" => "required",
                "year" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "The given data was invalid", $validator->errors());
            }

            $is_distributor = $request->is_distributor === true ? true : false;
            // $start_date = Carbon::now()->subYear()->startOfYear()->format('Y-m-d H:i:s');
            // $end_date = Carbon::now()->endOfMonth();

            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with("areaDistrictDealer.subRegion");
                    },
                    "invoice",
                ])
                ->where("status", "confirmed")

                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })
                ->when($request->has("year"), function ($q) use ($request) {

                    return $q->where(function ($Q) use ($request) {
                        return $Q
                            ->where(function ($Q) use ($request) {
                                return $Q
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($Q) use ($request) {
                                        return $Q
                                            ->whereYear("created_at", $request->year);
                                    });
                            })
                            ->orWhere(function ($Q) use ($request) {
                                return $Q
                                    ->where("type", "2")
                                    ->whereYear("date", $request->year);
                            });
                    });
                })

                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($QQQ) use ($request, $is_distributor) {
                        return $QQQ
                            ->whereHas("dealer", function ($QQQ) use ($request, $is_distributor) {
                                return $QQQ
                                    ->when($is_distributor === true, function ($QQQ) use ($request) {
                                        return $QQQ
                                            ->where("is_distributor", "1")
                                            ->whereHas("distributorContract", function ($QQQ) {
                                                return $QQQ
                                                    ->whereDate("contract_start", "<=", now()->format("Y-m-d"))
                                                    ->whereDate("contract_end", ">=", now()->format("Y-m-d"));
                                            });
                                    })
                                    ->subRegion($request->sub_region_id)
                                    ->withTrashed();
                            })
                            ->orWhereHas("subDealer", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->subRegion($request->sub_region_id)
                                    ->withTrashed();
                            });
                    });
                })
                ->get();

            $sales_orders_grouped_marketing_one_years = collect($sales_orders)
                ->groupBy([
                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->format('Y');
                        } else {
                            return Carbon::parse($val->created_at)->format('Y');
                        }
                    },
                    function ($val) {
                        return $val->personel?->id;
                    },

                    function ($val) {
                        if ($val->invoice) {
                            return $val->invoice->created_at->translatedFormat('F');
                        } else {
                            return Carbon::parse($val->created_at)->translatedFormat('F');
                        }
                    },
                ]);

            $detail = [
                // "sub_region" => null
            ];
            $month = ['Januari' => 0, 'Februari' => 0, 'Maret' => 0, 'April' => 0, 'Mei' => 0, 'Juni' => 0, 'Juli' => 0, 'Agustus' => 0, 'September' => 0, 'Oktober' => 0, 'November' => 0, 'Desember' => 0];

            foreach ($sales_orders_grouped_marketing_one_years as $years => $yearsvalue) {
                foreach ($yearsvalue as $personel_id => $personelvalues) {
                    $detail[$personel_id]["marketing"] = null;
                    $detail[$personel_id]["month"] = $month;
                }
            }

            foreach ($sales_orders_grouped_marketing_one_years as $years => $yearsvalue) {
                foreach ($yearsvalue as $personel_id => $personelvalues) {
                    $total_indirect = 0;
                    $total_direct = 0;
                    foreach ($personelvalues as $month => $monthvalue) {
                        foreach ($monthvalue as $ord) {
                            if ($ord->type == "2") {
                                $total_indirect += $ord->total;
                            } else {
                                if ($ord->invoice) {
                                    $total_direct += $ord->invoice->total;
                                }
                            }
                            // if ($ord->dealer) {
                            //     $detail["sub_region"] = $ord->dealer->areaDistrictDealer->subRegion->name;
                            // } else {
                            //     $detail["sub_region"] = $ord->subdealer->areaDistrictSubDealer->subRegion->name;
                            // }
                            $detail[$personel_id]["year"] = $years;
                            $detail[$personel_id]["marketing"] = $ord->personel;
                            $detail[$personel_id]["month"][$month] = $total_direct + $total_indirect;
                        }
                    }
                }
            }

            $detail = collect($detail)->reject(function ($detail_analisys, $personel_id) {
                return !$personel_id;
            });

            $order_by_collect = $detail->all();
            if ($request->sorting_column == "marketing_name") {
                if ($request->order_type == "desc") {
                    $order_by_collect = $detail->sortByDesc('marketing.name', SORT_NATURAL | SORT_FLAG_CASE)->all();
                } else {
                    $order_by_collect = $detail->sortBy('marketing.name', SORT_NATURAL | SORT_FLAG_CASE)->all();
                }
            }

            return $this->response("00", "Success get analisys group by marketing per years and per month", $order_by_collect);
        } catch (\Throwable $th) {
            return $this->response("01", "Failed get analisys ggroup by marketing per years and per month", [
                "message" => $th->getMessage(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]);
        }
    }

    public function analisysGroupSubRegionProductFiveYear(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes

            $validator = Validator::make($request->all(), [
                "sub_region_id" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "The given data was invalid", $validator->errors());
            }

            $is_distributor = $request->is_distributor === true ? true : false;

            $start_date = Carbon::now()->subYears(4)->startOfYear()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->endOfMonth();

            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with("areaDistrictDealer.subRegion");
                    },
                    "subDealer",
                    "sales_order_detail.product.package",
                    "invoice",
                ])
                ->whereHas("sales_order_detail")
                ->where("status", "confirmed")

            /* filter subregion */
            // ->when($request->has("sub_region_id"), function ($qqq) use ($request) {
            //     return $qqq
            //         ->whereIn("personel_id", $this->personelListByArea($request->sub_region_id));
            // })

                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })

                ->where(function ($QQQ) use ($start_date, $request) {

                    return $QQQ->where(function ($Q) use ($start_date, $request) {
                        return $Q
                            ->where(function ($Q) use ($start_date, $request) {
                                return $Q
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($Q) use ($start_date, $request) {
                                        return $Q
                                            ->where("created_at", ">", $start_date);
                                    });
                            })
                            ->orWhere(function ($Q) use ($start_date, $request) {
                                return $Q
                                    ->where("type", "2")
                                    ->where("date", ">", $start_date);
                            });
                    });
                })
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($Q) use ($request, $is_distributor) {
                        $Q
                            ->whereHas("dealer", function ($Q) use ($request, $is_distributor) {
                                $Q->when($is_distributor == true, function ($q) use ($request) {
                                    return $q->where("is_distributor", "1")->whereHas("distributorContract");
                                })->subRegion($request->sub_region_id)->withTrashed();
                            })
                            ->orWhereHas("subDealer", function ($Q) use ($request) {
                                return $Q->subRegion($request->sub_region_id)->withTrashed();
                            });
                    });
                })
                ->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("dealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictDealer")->withTrashed();
                        })
                        ->orWhereHas("subDealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictSubDealer")->withTrashed();
                        });
                })
                ->get();

            $sales_order_detail = SalesOrderDetail::query()
                ->with([
                    "sales_order" => function ($QQQ) {
                        return $QQQ
                            ->with([
                                "invoice",
                                "dealer" => function ($QQQ) {
                                    return $QQQ->with([
                                        "areaDistrictDealer" => function ($QQQ) {
                                            return $QQQ->with([
                                                "subRegion",
                                            ]);
                                        },
                                    ]);
                                },
                                "subDealer" => function ($QQQ) {
                                    return $QQQ->with([
                                        "areaDistrictSubDealer" => function ($QQQ) {
                                            return $QQQ->with([
                                                "subRegion",
                                            ]);
                                        },
                                    ]);
                                },
                            ]);
                    },
                    "product.package",
                ])
                ->whereIn("sales_order_id", $sales_orders->pluck("id"))
                ->get();

            $sales_order_detail_grouped = $sales_order_detail->groupBy([
                function ($val) {
                    return $val->product_id;
                },
                function ($val) {
                    return confirmation_time($val->sales_order)->format('Y');
                },
            ]);

            $periodyear = CarbonPeriod::create($start_date, $end_date);
            $recap = [
                // "sub_region" => null
            ];

            foreach ($sales_order_detail_grouped as $product_id => $values) {
                $recap[$product_id]["product"] = null;
                $recap[$product_id]["total_sales_five_years"] = 0;

                foreach ($periodyear as $years => $value) {
                    $recap[$product_id]["detail_peryear"][$value->format('Y')] = 0;
                }
            }

            foreach ($sales_order_detail_grouped as $product_id => $values) {
                $total_product_five_years = 0;
                $total_product = 0;
                foreach ($values as $years => $value) {
                    $total_product_five_years = $value->sum("quantity");
                    $total_product += $total_product_five_years;
                    $recap[$product_id]["detail_peryear"][$years] = $value->sum("quantity");
                    foreach ($value as $key => $ord) {
                        // $total_product = $ord->sales_order->sales_order_detail->sum("quantity_on_package");
                        $recap[$product_id]["product"] = $ord->product;
                    }
                    if ($ord->sales_order->dealer) {
                        $recap[$product_id]["store_type"] = "dealer";
                        // $recap["sub_region"] = $ord->sales_order->dealer->areaDistrictDealer->subRegion->name;
                        $recap[$product_id]["sub_region_id"] = $ord->sales_order->dealer->areaDistrictDealer->subRegion->id;
                    } else {

                        $recap[$product_id]["store_type"] = "sub_dealer";
                        $recap[$product_id]["sub_region_id"] = $ord->sales_order->subDealer->areaDistrictSubDealer->subRegion->id;
                    }
                    // $recap[$product_id]["sales_order"][$years] = $value->sum("quantity_on_package");
                }
                $recap[$product_id]["total_sales_five_years"] = $total_product;
                $recap[$product_id]["sales_order_id"] = $ord->sales_order_id;
                $recap[$product_id]["product_id"] = $product_id;
            }

            // Setup necessary information for LengthAwarePaginator
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;
            if ($request->sorting_column == 'product_name') {
                if ($request->order_type == 'desc') {
                    $recap = collect($recap)->sortByDesc('product.name', SORT_NATURAL | SORT_FLAG_CASE)->all();
                } else {
                    $recap = collect($recap)->sortBy('product.name', SORT_NATURAL | SORT_FLAG_CASE)->all();
                }
            }
            // slice the current page items
            $currentItems = collect($recap)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $paginator = new LengthAwarePaginator($currentItems, count($recap), $pageLimit, $currentPage, ['path' => $path]);

            // if ($request->sort_by && in_array($request->sort_by, array_values(array_unique($yeard)))) {
            //     if ($request->direction == "desc") {
            //         $year = strval($request->sort_by);
            //         $sortedResult = $paginator->getCollection()->sortByDesc(function ($item) use ($year) {
            //             return $item["years"][$year];
            //         })->values();
            //     } elseif ($request->direction == "asc") {
            //         $sortedResult = $paginator->getCollection()->sortBy(function ($item)  use ($year) {
            //             return $item["years"][$year];
            //         })->values();
            //     }

            //     $paginator->setCollection($sortedResult);
            // }

            return $this->response("00", "Success get analisys group by marketing per years and per month", $paginator);
        } catch (\Throwable $th) {
            return $this->responseAsJson("01", "Failed get analisys group by product per years", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    public function analisysGroupSubRegionProductDealerFiveYear(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes

            $validator = Validator::make($request->all(), [
                "sub_region_id" => "required",
                "product_id" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "The given data was invalid", $validator->errors());
            }

            $is_distributor = $request->is_distributor === true ? true : false;

            $start_date = Carbon::now()->subYears(4)->startOfYear()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->endOfMonth();

            // filter by product_id and sub region
            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with("areaDistrictDealer.subRegion");
                    },
                    "sales_order_detail.product.package",
                    "invoice",
                ])
                ->whereHas("sales_order_detail")
                ->where("status", "confirmed")

                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })

                ->where(function ($QQQ) use ($start_date, $request) {

                    return $QQQ->where(function ($Q) use ($start_date, $request) {
                        return $Q
                            ->where(function ($Q) use ($start_date, $request) {
                                return $Q
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($Q) use ($start_date, $request) {
                                        return $Q
                                            ->where("created_at", ">", $start_date);
                                    });
                            })
                            ->orWhere(function ($Q) use ($start_date, $request) {
                                return $Q
                                    ->where("type", "2")
                                    ->where("date", ">", $start_date);
                            });
                    });
                })
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $is_distributor) {
                    return $QQQ->where(function ($Q) use ($request, $is_distributor) {
                        $Q
                            ->whereHas("dealer", function ($Q) use ($request, $is_distributor) {
                                $Q->when($is_distributor == true, function ($q) use ($request) {
                                    return $q->where("is_distributor", "1")->whereHas("distributorContract");
                                })->subRegion($request->sub_region_id)->withTrashed();
                            })
                            ->orWhereHas("subDealer", function ($Q) use ($request) {
                                return $Q->subRegion($request->sub_region_id)->withTrashed();
                            });
                    });
                })
                ->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("dealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictDealer")->withTrashed();
                        })
                        ->orWhereHas("subDealer", function ($Q) use ($request) {
                            return $Q->whereHas("areaDistrictSubDealer")->withTrashed();
                        });
                })
                ->get();

            $sales_order_detail = SalesOrderDetail::query()
                ->with([
                    "sales_order" => function ($QQQ) {
                        return $QQQ
                            ->with("invoice", "dealer.grading", "subDealer");
                    },
                    "product.package",
                ])
                ->when($request->has("product_id"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("product", function ($Q) use ($request) {
                        return $Q->where("id", $request->product_id);
                    });
                })
                ->whereIn("sales_order_id", $sales_orders->pluck("id"))
                ->get();

            $sales_order_detail_grouped = $sales_order_detail->groupBy([
                function ($val) {
                    if ($val->sales_order->dealer) {
                        return $val->sales_order->dealer->id;
                    } else {
                        return $val->sales_order->subDealer->id;
                    }
                },
                function ($val) {
                    return confirmation_time($val->sales_order)->format('Y');
                },
            ]);

            $periodyear = CarbonPeriod::create($start_date, $end_date);
            $recap = [];

            foreach ($sales_order_detail_grouped as $dealer_id => $values) {
                $recap[$dealer_id]["store"] = null;
                $recap[$dealer_id]["quantity_unit"] = 0;

                foreach ($periodyear as $years => $value) {
                    $recap[$dealer_id]["detail_peryear"][$value->format('Y')] = 0;
                }
            }

            foreach ($sales_order_detail_grouped as $dealer_id => $values) {
                $total_product_five_years = 0;
                foreach ($values as $years => $value) {
                    $total_product = $value->sum('quantity');
                    $total_product_five_years += $total_product;
                    foreach ($value as $key => $ord) {
                        // $recap["product"] = $ord->product;
                        if ($ord->sales_order->dealer) {
                            // $recap["sub_region"] = $ord->sales_order->dealer->areaDistrictDealer->subRegion->name. "cekk";
                            $recap[$dealer_id]["store"] = $ord->sales_order->dealer;
                            $recap[$dealer_id]["store"]["store_id"] = $ord->sales_order->dealer->dealer_id;
                            $recap[$dealer_id]["store_type"] = "dealer";
                        } else {
                            // $recap["sub_region"] = $ord->sales_order->sub_dealer->areaDistrictSubDealer->subRegion->name;
                            $recap[$dealer_id]["store"] = $ord->sales_order->subDealer;
                            $recap[$dealer_id]["store"]["store_id"] = $ord->sales_order->subDealer->sub_dealer_id;
                            $recap[$dealer_id]["store_type"] = "sub_dealer";
                        }
                    }

                    $recap[$dealer_id]["detail_peryear"][$years] = $total_product;
                }
                $recap[$dealer_id]["quantity_unit"] = $total_product_five_years;

                // $recap["recap"][$dealer_id]["sales_order_id"] = $ord->sales_order_id;
            }

            if ($request->sorting_column == 'dealer_id') {
                if ($request->order_type == 'desc') {
                    $recap = collect($recap)->sortByDesc('store.store_id', SORT_NATURAL | SORT_FLAG_CASE)->all();
                } else {
                    $recap = collect($recap)->sortBy('store.store_id', SORT_NATURAL | SORT_FLAG_CASE)->all();
                }
            }
            // return $recap;
            // Setup necessary information for LengthAwarePaginator
            // $currentPage = LengthAwarePaginator::resolveCurrentPage();
            // $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // // slice the current page items
            // $currentItems = collect($recap)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // // you may not need the $path here but might be helpful..
            // $path = LengthAwarePaginator::resolveCurrentPath();

            // // Build the new paginator
            // $paginator = new LengthAwarePaginator($currentItems, count($recap), $pageLimit, $currentPage, ['path' => $path]);

            return $this->response("00", "Success get analisys group by product per dealer and per years", $recap);
        } catch (\Throwable $th) {
            return $this->response("01", "Failed get analisys group by product  per dealer and per years", $th->getMessage());
        }
    }

    public function saleorOrderLast1Year(Request $request)
    {
        try {

            $start_date = Carbon::createFromFormat('Y-m-d', date('Y') . '-' . '12' . '-' . '31')->subMonth(12);
            $end_date = Carbon::createFromFormat('Y-m-d', date('Y') . '-' . '12' . '-' . '31');

            // buat query yang isinya sub region saja
            $subReqion = SubRegion::when($request->region, function ($QQQ) use ($request) {
                return $QQQ->where("region_id", $request->region);
            })
                ->when($request->sub_region, function ($QQQ) use ($request) {
                    return $QQQ->where("id", $request->sub_region);
                })->groupBy('name')->get();

            $detail = [];
            $sub_sub_region = collect($subReqion);
            $sales_order_last_4month = $this->sales_order->query()
                ->Join('personels', 'personels.id', '=', 'sales_orders.personel_id')
                ->Join("marketing_area_sub_regions", "marketing_area_sub_regions.personel_id", "personels.id")
                ->select(
                    DB::raw("marketing_area_sub_regions.id as sub_region_id"),
                    DB::raw("marketing_area_sub_regions.name"),
                    DB::raw("(sum(sales_orders.total)) as total"),
                    DB::raw("(DATE_FORMAT(sales_orders.date, '%m-%Y')) as month"),
                    DB::raw("(DATE_FORMAT(sales_orders.date, '%Y')) as year"),
                )
                ->whereBetween('sales_orders.date', [$start_date, $end_date])
                ->groupBy(DB::raw("DATE_FORMAT(sales_orders.date, '%m-%Y')"))

                ->groupBy(DB::raw("marketing_area_sub_regions.id"))
                ->get();

            foreach ($sub_sub_region as $key => $value) {

                $sales_collect_order_last_4month = collect($sales_order_last_4month);
                foreach ($sales_collect_order_last_4month as $key => $sales) {
                    $detail[$value->name][] = $sales->name == $value->name ? $sales : "-";
                }
            }

            return $this->response('00', 'success, sales order last 1 year', $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get sales order last 1 year", $th->getMessage());
        }
    }

    public function saleorOrderByYear(Request $request)
    {
        if ($request->start_year && $request->end_year) {

            $start_date = date('Y-m-d', strtotime($request->start_year . "-01-01"));
            $end_date = date('Y-m-d', strtotime($request->end_year . "-12-31"));
        } else {
            $start_date = Carbon::now()->subYear(3)->format("Y-m-d");
            $end_date = Carbon::now()->format("Y-m-d");
        }

        // wilayah jogja marketinge sopo wae ?
        // region --> kec -> dis id -> dealer -> alamat dealer

        $salesOrderByYear = $this->sales_order->query()
            ->when($request->region, function ($QQQ) use ($request) {
                // return $QQQ->whereHas('dealer', function ($query) use ($request) {
                //     return $query->where("is_distributor", 0);
                // });
                return $QQQ->whereHas('dealer.adress_detail', function ($query) use ($request) {
                    $all_district = $this->districtListByAreaId($request->region);

                    return $query->whereIn('district_id', $all_district);
                });
            })
            ->Join('invoices', 'sales_orders.id', '=', 'invoices.sales_order_id')
            ->where('sales_orders.type', 1)
            ->where('sales_orders.status', 'confirmed')
            ->select(
                DB::raw("(sum(invoices.sub_total)) as total, sales_orders.date as created_at"),
                DB::raw("(DATE_FORMAT(sales_orders.date, '%Y')) as year"),
                DB::raw("(DATE_FORMAT(sales_orders.date, '%m-%Y')) as month"),
                // DB::raw('MONTH(sales_orders.created_at) month'),
                // DB::raw('YEAR(sales_orders.created_at) year')
            )

            ->groupBy(DB::raw("DATE_FORMAT(sales_orders.date, '%Y')"))
            ->groupBy(DB::raw("DATE_FORMAT(sales_orders.date, '%m-%Y')"))
        //  ->groupby('month')
            ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                return $QQQ->whereBetween('created_at', [$start_date, $end_date])
                    ->where("payment_status", "settle");
            })
        // ->whereBetween('sales_orders.date', [$start_date, $end_date])
            ->get();

        $detail = [];
        $year = $start_date;
        $period = CarbonPeriod::create($start_date, $end_date)->year();
        // return $period;
        // $x = [];
        foreach ($period as $date) {
            $detail[$date->format('Y')] = [
                "jan-" . $date->format('Y') => 0,
                "feb-" . $date->format('Y') => 0,
                "mar-" . $date->format('Y') => 0,
                "apr-" . $date->format('Y') => 0,
                "mei-" . $date->format('Y') => 0,
                "jun-" . $date->format('Y') => 0,
                "jul-" . $date->format('Y') => 0,
                "aug-" . $date->format('Y') => 0,
                "sep-" . $date->format('Y') => 0,
                "okt-" . $date->format('Y') => 0,
                "nov-" . $date->format('Y') => 0,
                "des-" . $date->format('Y') => 0,
            ];
        }

        $arraydate = [];
        $date_and_total = [];
        $salesOrderByYearGroup = collect($salesOrderByYear);
        foreach ($salesOrderByYearGroup as $key => $month) {
            $date_and_total += [$month->created_at->format('M-Y') => $month->total];
        }

        foreach ($salesOrderByYearGroup as $month) {
            $arraydate[] = $month->created_at->format('M-Y');
            $months = ['Jan-' . $month->year => 0, 'Feb-' . $month->year => 0, 'Mar-' . $month->year => 0, 'Apr-' . $month->year => 0, 'May-' . $month->year => 0, 'Jun-' . $month->year => 0, 'Jul-' . $month->year => 0, 'Aug-' . $month->year => 0, 'Sep-' . $month->year => 0, 'Oct-' . $month->year => 0, 'Nov-' . $month->year => 0, 'Dec-' . $month->year => 0];
            $detail[$month->year] = $months;

            foreach ($detail[$month->year] as $key => $value) {
                $detail[$month->year][$key] = in_array($key, $arraydate) ? $date_and_total[$key] : 0;
            }
        }

        return $this->response('00', 'success, get saleorder by year', $detail);
    }

    public function productPerCustomer(Request $request)
    {
        $productSubDealer = $this->sales_order->query()
            ->when($request->region, function ($QQQ) use ($request) {
                // return $QQQ->whereHas('dealer', function ($query) use ($request) {
                //     return $query->where("is_distributor", 1);
                // });
                return $QQQ->whereHas('dealer.adress_detail', function ($query) use ($request) {
                    $all_district = $this->districtListByAreaId($request->region);

                    return $query->whereIn('district_id', $all_district);
                });
            })
            ->when($request->sub_region, function ($QQQ) use ($request) {
                return $QQQ->whereHas('dealer.adress_detail', function ($query) use ($request) {
                    $all_district = $this->districtListByAreaId($request->sub_region);

                    return $query->whereIn('district_id', $all_district);
                });
            })
            ->when($request->dealer, function ($QQQ) use ($request) {
                return $QQQ->whereHas('dealer', function ($query) use ($request) {
                    return $query->where('id', $request->dealer);
                });
            })
            ->when($request->year, function ($QQQ) use ($request) {
                return $QQQ->whereYear('sales_orders.date', $request->year);
            })
            ->when($request->month, function ($QQQ) use ($request) {
                return $QQQ->whereMonth("sales_orders.date", $request->month);
            })
            ->when($request->product, function ($QQQ) use ($request) {
                return $QQQ->whereHas('sales_order_detail', function ($query) use ($request) {
                    return $query->where('product_id', $request->product);
                });
            })
            ->select(
                DB::raw("sales_order_details.product_id as product_id, products.name as name, invoices.sales_order_id as sales_order_id , sum(sales_order_details.quantity) as quantity"),
            )
            ->Join('sales_order_details', 'sales_order_details.sales_order_id', '=', 'sales_orders.id')
            ->Join('products', 'sales_order_details.product_id', '=', 'products.id')
            ->Join('packages', 'sales_order_details.package_id', '=', 'packages.id')

            ->Join('invoices', 'invoices.sales_order_id', '=', 'sales_orders.id')

        // ->count("sales_order_details.product_id")
            ->whereHas('dealer')
        // ->whereHas('sales_order_detail_product')
            ->whereHas('invoices')->where('packages.isActive', 1)
            ->where('sales_orders.status', "confirmed")
            ->get();

        $group_product = collect($productSubDealer)->groupBy("product_id");

        $detail = [
            "name" => null,
            "quantity" => 0,
        ];

        // $ranking = [];

        foreach ($group_product as $product_id => $value) {
            $detail["name"] = $value[0]->name;
            $detail["quantity"] = $value[0]->quantity;
            $ranking[$product_id] = $detail;
        }

        // $ranking = collect($ranking)->sortByDesc("count_dealer");
        return $this->response("00", "Statistic Product per Customer", $detail);
    }

    public function saleorOrderDiagramDirectSales(Request $request)
    {

        $sales_orders = null;

        try {

            //$personels_id = $this->personelListByArea();

            if ($request->has("region_id")) {
                $personels_id = $this->personelListByArea($request->region_id);
                unset($request->sub_region_id);
            } else {
                $personels_id = $this->personelListByArea();
                $has_request = true;
            }

            if ($request->year) {

                $start_date = date('Y-m-d', strtotime($request->year . "-01-01"));
                $end_date = date('Y-m-d', strtotime($request->year . "-12-31"));
            } else {
                $start_date = Carbon::createFromFormat('Y-m-d', date('Y') . '-' . '12' . '-' . '31')->subMonth(12);
                $end_date = Carbon::createFromFormat('Y-m-d', date('Y') . '-' . '12' . '-' . '31');
            }
            $detail = [];

            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing" => function ($Q) {
                                return $Q->with([
                                    "subRegionWithRegion" => function ($Q) {
                                        return $Q->with([
                                            "region",
                                        ]);
                                    },
                                ]);
                            },
                        ]);
                    },
                    "invoice",
                    "sales_order_detail",
                ])
                ->where("status", "confirmed")
                ->where("type", 1)

            /* filter region */
                ->when($request->has("region_id"), function ($qqq) use ($request) {
                    return $qqq
                        ->whereIn("personel_id", $this->personelListByArea($request->region_id));
                })

            /* if filter personel */
                ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("personel_id", $this->getChildren($request->personel_id));
                })

            /* if no filter */
                ->when(!$request->has("personel_id") && !$request->has("region_id"), function ($QQQ) use ($personels_id) {
                    return $QQQ->whereIn("personel_id", $personels_id);
                })

                ->whereHas("invoice", function ($QQQ) use ($start_date, $end_date) {
                    return $QQQ->whereBetween('created_at', [$start_date, $end_date]);
                })
                ->get();

            if ($request->has("region_id")) {
                $sales_orders_grouped = $sales_orders->groupBy([
                    function ($val) {
                        return $val->personel->areaMarketing->subRegionWithRegion->id;
                    },
                    function ($val) {
                        return $val->created_at->format('Y');
                    },
                ]);

                foreach ($sales_orders_grouped as $sub_region => $values) {
                    foreach ($values as $key => $value) {
                        $total = 0;
                        foreach ($value as $key => $ord) {
                            if ($ord->invoice) {
                                if ($ord->invoice->payment_status == "settle") {
                                    $total += $ord->invoice->total;
                                }
                            }
                            $detail[$sub_region]["sub_region"] = $ord->personel->areaMarketing->subRegionWithRegion->name;
                        }
                        $detail[$sub_region]["created_at"] = $ord->created_at;
                        $detail[$sub_region]["total"] = $total;
                    }
                }
                $total_total = 0;
                foreach ($detail as $key => $values) {
                    $total_total += $values['total'];
                    // $detail["total_total"] = $total_total;
                }
                foreach ($detail as $key => $values) {

                    $detail[$key]["persentase"] = $values['total'] / $total_total * 100;
                }
            } else {
                $sales_orders_grouped = $sales_orders->groupBy([
                    function ($val) {
                        return $val->personel->areaMarketing->subRegionWithRegion->region->id;
                    },
                    function ($val) {
                        return $val->created_at->format('Y');
                    },
                ]);

                foreach ($sales_orders_grouped as $sub_region => $values) {
                    foreach ($values as $key => $value) {
                        $total = 0;
                        foreach ($value as $key => $ord) {
                            if ($ord->invoice) {
                                if ($ord->invoice->payment_status == "settle") {
                                    $total += $ord->invoice->total;
                                }
                            }
                            $detail[$sub_region]["region"] = $ord->personel->areaMarketing->subRegionWithRegion->region->name;
                        }
                        $detail[$sub_region]["created_at"] = $ord->created_at;
                        $detail[$sub_region]["total"] = $total;
                    }
                }
                $total_total = 0;
                foreach ($detail as $key => $values) {
                    $total_total += $values['total'];
                    // $detail["total_total"] = $total_total;
                }
                foreach ($detail as $key => $values) {

                    $detail[$key]["persentase"] = $values['total'] / $total_total * 100;
                }
            }

            return $this->response("00", "Statistic Diagram Direct Sales", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get Statistic Diagram Direct Sales", $th->getMessage());
        }
    }

    public function saleorOrderDiagramPaymentTime(Request $request)
    {
        try {

            ini_set('max_execution_time', 1500); //3 minutes

            if ($request->start_year && $request->end_year) {

                $start_date = date('Y-m-d', strtotime($request->start_year . "-01-01"));
                $end_date = date('Y-m-d', strtotime($request->end_year . "-12-31"));
            } else {
                $start_date = Carbon::now()->startOfYear(1);
                $end_date = Carbon::now();
            }
            // return $invoice;

            $sales_orders = Invoice::query()
                ->with([
                    "payment" => function ($QQQ) {
                        return $QQQ
                            ->orderBy("created_at", "desc");
                    },
                    "salesOrderOnly.dealer.adress_detail",
                ])
            // ->whereHas("payment", function ($QQQ) {
            //     return $QQQ
            //         ->orderBy("created_at", "desc");
            // })
                ->when($request->region, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                        $marketing_list = $this->marketingListByAreaId($request->region);
                        $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
                        return $query->whereIn("sales_order_id", $sales_orders);
                    });
                })
                ->when($request->sub_region, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                        $marketing_list = $this->marketingListByAreaId($request->sub_region);
                        $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
                        return $query->whereIn("sales_order_id", $sales_orders);
                    });
                })
                ->when($request->dealer, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                        return $query->where('store_id', $request->dealer);
                    });
                })
                ->whereDate('created_at', ">=", $start_date)
                ->whereDate('created_at', "<=", $end_date)
                ->get();

            $detail = [
                "payment_time" => null,
                "settle_vs_open" => null,
            ];

            $sales_orders->map(function ($invoice, $key) {
                $payment_days = 0;
                if (count($invoice->payment)) {
                    $payment_days = $invoice->created_at->diffInDays($invoice->payment[0]->created_at);
                }
                $invoice->payment_days = $payment_days;
            });

            $days0_30 = $sales_orders->where("payment_days", "<=", 30)->where("payment_status", "settle")->count();
            $days31_60 = $sales_orders->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->where("payment_status", "settle")->count();
            $days61_90 = $sales_orders->where("payment_days", ">=", 61)->where("payment_days", "<=", 90)->where("payment_status", "settle")->count();
            $more90_days = $sales_orders->where("payment_days", ">", 90)->where("payment_status", "settle")->count();

            $days0_30_nominal = $sales_orders->where("payment_days", "<=", 30)->where("payment_status", "settle")->sum("total") + $sales_orders->where("payment_days", "<=", 30)->where("payment_status", "settle")->sum("ppn");
            $days31_60_nominal = $sales_orders->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->where("payment_status", "settle")->sum("total") + $sales_orders->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->where("payment_status", "settle")->sum("ppn");
            $days61_90_nominal = $sales_orders->where("payment_days", ">=", 61)->where("payment_days", "<=", 90)->where("payment_status", "settle")->sum("total") + $sales_orders->where("payment_days", ">=", 61)->where("payment_days", "<=", 90)->where("payment_status", "settle")->sum("ppn");
            $more90_days_nominal = $sales_orders->where("payment_days", ">", 90)->where("payment_status", "settle")->sum("total") + $sales_orders->where("payment_days", ">", 90)->where("payment_status", "settle")->sum("ppn");

            $lebihan = $sales_orders->where("remaining", "<", 0)->where("payment_status", "settle")->count();

            $lebihan_nominal = collect($lebihan)->sum('total') + $sales_orders->where("remaining", "<", 0)->Where("payment_status", "settle")->sum('ppn');

            $total = $days0_30 + $days31_60 + $days61_90 + $more90_days + $lebihan;

            $lunas = $sales_orders->where("payment_status", "settle")->count();
            $lunas_nominal = $sales_orders->where("payment_status", "settle")->sum('total') + $sales_orders->where("payment_status", "settle")->sum('ppn');

            $belum_bayar = $sales_orders->where("payment_status", "unpaid")->count();
            $belum_bayar_nominal = $sales_orders->where("payment_status", "unpaid")->sum('total') + $sales_orders->where("payment_status", "unpaid")->sum('ppn');

            $belum_lunas = $sales_orders->whereIn("payment_status", "paid")->count();
            $belum_lunas_nominal = $sales_orders->where("payment_status", "paid")->sum('total') + $sales_orders->where("payment_status", "paid")->sum('ppn');

            $totallunasvsopen = $lunas + $belum_lunas + $belum_bayar;

            // $belum_lunas_less_90 = $sales_orders->where("payment_days", "<=", 90)->whereIn("payment_status", ["paid", "unpaid"])->count();
            // $belum_lunas_more_90 = $sales_orders->where("payment_days", ">", 90)->whereIn("payment_status", ["paid", "unpaid"])->count();

            // $paymentDayColor = PaymentDayColor::select("id", "min_days", "max_days", "bg_color", "text_color")->get();

            // $detail["payment_time"]["0_30days"]["color"] =  PaymentDayColor::select("id","bg_color", "text_color","min_days","max_days")->where("min_days",">=",0)->where("max_days","<=",30)->get();

            $detail["payment_time"]["0_30days"]["count_invoice"] = $days0_30;
            $detail["payment_time"]["31_60days"]["count_invoice"] = $days31_60;
            $detail["payment_time"]["61_90days"]["count_invoice"] = $days61_90;
            $detail["payment_time"]["more_90_days"]["count_invoice"] = $more90_days;
            $detail["payment_time"]["lebihan"]["count_invoice"] = $lebihan;

            $detail["payment_time"]["0_30days"]["nominal_invoice"] = $days0_30_nominal;
            $detail["payment_time"]["31_60days"]["nominal_invoice"] = $days31_60_nominal;
            $detail["payment_time"]["61_90days"]["nominal_invoice"] = $days61_90_nominal;
            $detail["payment_time"]["more_90_days"]["nominal_invoice"] = $more90_days_nominal;
            $detail["payment_time"]["lebihan"]["nominal_invoice"] = $lebihan_nominal;

            $detail["payment_time"]["0_30days"]["persentage"] = $days0_30 / ($total ?: 1) * 100;
            $detail["payment_time"]["31_60days"]["persentage"] = $days31_60 / ($total ?: 1) * 100;
            $detail["payment_time"]["61_90days"]["persentage"] = $days61_90 / ($total ?: 1) * 100;
            $detail["payment_time"]["more_90_days"]["persentage"] = $more90_days / ($total ?: 1) * 100;
            $detail["payment_time"]["lebihan"]["persentage"] = $lebihan / ($total ?: 1) * 100;

            $detail["settle_vs_open"]["lunas"]["count_invoice"] = $lunas;
            $detail["settle_vs_open"]["belum_lunas"]["count_invoice"] = $belum_lunas;
            $detail["settle_vs_open"]["belum_bayar"]["count_invoice"] = $belum_bayar;

            $detail["settle_vs_open"]["lunas"]["persentage"] = $lunas / ($totallunasvsopen ?: 1) * 100;
            $detail["settle_vs_open"]["belum_lunas"]["persentage"] = $belum_lunas / ($totallunasvsopen ?: 1) * 100;
            $detail["settle_vs_open"]["belum_bayar"]["persentage"] = $belum_bayar / ($totallunasvsopen ?: 1) * 100;

            $detail["settle_vs_open"]["lunas"]["nominal_invoice"] = $lunas_nominal;
            $detail["settle_vs_open"]["belum_lunas"]["nominal_invoice"] = $belum_lunas_nominal;
            $detail["settle_vs_open"]["belum_bayar"]["nominal_invoice"] = $belum_bayar_nominal;

            // $detail["total_lunas_payment_time"] = $total;

            $detail["total_lunas_all"] = $lunas;
            // $detail["total_belum_lunas_less_90"] = $belum_lunas_less_90;
            // $detail["total_belum_lunas_more_90"] = $belum_lunas_more_90;
            // $detail["count_invoice_out"] = $lunas + $belum_lunas_less_90 + $belum_lunas_more_90;

            return $this->response("00", "Statistic Diagram Payment Time", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get Statistic Diagram Payment Time", $th->getMessage());
        }
    }

    public function saleorOrderDiagramPaymentTimeV3(Request $request)
    {
        // $cek = [];
        if ($request->start_year && $request->end_year) {

            $start_date = date('Y-m-d', strtotime($request->start_year . "-01-01"));
            $end_date = date('Y-m-d', strtotime($request->end_year . "-12-31"));
        } else {
            $start_date = Carbon::now()->startOfYear(1);
            $end_date = Carbon::now();
        }

        $invoices = Invoice::query()
            ->with([
                "payment" => function ($QQQ) {
                    return $QQQ
                        ->orderBy("created_at", "desc");
                },
                "salesOrderOnly.dealer.adress_detail",
            ])
        // ->whereHas("payment", function ($QQQ) {
        //     return $QQQ
        //         ->orderBy("created_at", "desc");
        // })
            ->when($request->region, function ($QQQ) use ($request) {
                return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                    $marketing_list = $this->marketingListByAreaId($request->region);
                    $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
                    return $query->whereIn("sales_order_id", $sales_orders);
                });
            })
            ->when($request->sub_region, function ($QQQ) use ($request) {
                return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                    $marketing_list = $this->marketingListByAreaId($request->sub_region);
                    $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
                    return $query->whereIn("sales_order_id", $sales_orders);
                });
            })
            ->when($request->dealer, function ($QQQ) use ($request) {
                return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                    return $query->where('store_id', $request->dealer);
                });
            })
            ->whereDate('created_at', ">=", $start_date)
            ->whereDate('created_at', "<=", $end_date)
            ->get();

        // return $invoices;
        $invoices->map(function ($invoice, $key) {
            $payment_days = 0;
            if (count($invoice->payment)) {
                $payment_days = $invoice->created_at->diffInDays($invoice->payment[0]->created_at);
            }
            $invoice->payment_days = $payment_days;
        });
        // dd($invoices);

        $invoice_count = $invoices->where("payment_status", "settle")->count();

        $invoice_count_all = $invoices->count();

        // $invoice_sum = $invoices->sum("total") + $invoices->sum("ppn");

        $lebihan = $invoices->where("remaining", "<", 0)->where("payment_status", "settle")->count();

        $lebihan_nominal = collect($lebihan)->where("payment_status", "settle")->sum('total') + $invoices->where("payment_status", "settle")->where("remaining", "<", 0)->sum('ppn');

        $lunas = $invoices->where("payment_status", "settle")->count();
        $lunas_nominal = $invoices->where("payment_status", "settle")->sum('total') + $invoices->where("payment_status", "settle")->sum('ppn');

        $belum_bayar = $invoices->where("payment_status", "unpaid")->count();
        $belum_bayar_nominal = $invoices->where("payment_status", "unpaid")->sum('total') + $invoices->where("payment_status", "unpaid")->sum('ppn');

        // dd($belum_bayar);
        $belum_lunas = $invoices->where("payment_status", "paid")->count();
        $belum_lunas_nominal = $invoices->where("payment_status", "paid")->sum('total') + $invoices->where("payment_status", "paid")->sum('ppn');

        $ddd = [
            "payment_time" => null,
        ];
        PaymentDayColor::select("id", "min_days", "max_days", "bg_color", "text_color")
            ->orderBy("min_days", "asc")
            ->get()->map(function ($data, $key) use ($invoices, &$ddd, $invoice_count) {
            $invoiceDaysCount = $invoices->where("payment_days", ">=", $data->min_days)->where("payment_status", "settle")->where("payment_days", "<=", $data->max_days)->count();
            $invoicesDaysSum = $invoices->where("payment_days", ">=", $data->min_days)->where("payment_status", "settle")->where("payment_days", "<=", $data->max_days)->sum("total") + $invoices->where("payment_days", ">=", $data->min_days)->where("payment_days", "<=", $data->max_days)->sum("ppn");
            $ddd["payment_time"][$data->min_days . "_" . $data->max_days . "days"] = [
                "count_invoice" => $invoiceDaysCount,
                "nominal_invoice" => $invoicesDaysSum,
                "bg_color" => $data->bg_color,
                "text_color" => $data->text_color,
                "persentage" => ($invoiceDaysCount != 0) ? $invoiceDaysCount / $invoice_count * 100 : 0,
            ];
        });

        $ddd["payment_time"]["lebihan"] = [
            "count_invoice" => $lebihan,
            "bg_color" => "000000",
            "text_color" => "ffffff",
            "nominal_invoice" => $lebihan_nominal,
            "persentage" => ($lebihan != 0) ? $lebihan / $invoice_count * 100 : 0,
        ];

        // dd($invoice_count_all);
        $ddd["settle_vs_open"]["lunas"] = [
            "count_invoice" => $lunas,
            "bg_color" => "00FF00",
            "nominal_invoice" => $lunas_nominal,
            "persentage" => ($lunas != 0) ? $lunas / $invoice_count_all * 100 : 0,
        ];

        $ddd["settle_vs_open"]["belum_bayar"] = [
            "count_invoice" => $belum_bayar,
            "bg_color" => "000000",
            "nominal_invoice" => $belum_bayar_nominal,
            "persentage" => ($belum_bayar != 0) ? $belum_bayar / $invoice_count_all * 100 : 0,
        ];

        $ddd["settle_vs_open"]["belum_lunas"] = [
            "count_invoice" => $belum_lunas,
            "bg_color" => "FF0000",
            "nominal_invoice" => $belum_lunas_nominal,
            "persentage" => ($belum_lunas != 0) ? $belum_lunas / $invoice_count_all * 100 : 0,
        ];

        $ddd["total_lunas_all"] = $lunas;
        return $this->response("00", "Statistic Diagram Payment Time", $ddd);
    }

    public function saleorOrderDiagramPaymentTimeNotSettle(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes
            if ($request->start_year && $request->end_year) {

                $start_date = date('Y-m-d', strtotime($request->start_year . "-01-01"));
                $end_date = date('Y-m-d', strtotime($request->end_year . "-12-31"));
            } else {
                $start_date = Carbon::now()->startOfYear();
                $end_date = Carbon::now();
            }
            // return $invoice;

            $sales_orders = Invoice::query()
                ->with([
                    "payment" => function ($QQQ) {
                        return $QQQ
                            ->orderBy("created_at", "desc");
                    },
                    "salesOrderOnly.dealer.adress_detail",
                ])
                ->whereHas("salesOrderOnly")
                ->when($request->region, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                        $marketing_list = $this->marketingListByAreaId($request->region);
                        $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
                        return $query->whereIn("sales_order_id", $sales_orders);
                    });
                })
                ->when($request->sub_region, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                        $marketing_list = $this->marketingListByAreaId($request->sub_region);
                        $sales_orders = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $marketing_list)->pluck("id");
                        return $query->whereIn("sales_order_id", $sales_orders);
                    });
                })
                ->when($request->dealer, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('salesOrderOnly', function ($query) use ($request) {
                        return $query->where('store_id', $request->dealer);
                    });
                })
                ->whereDate('created_at', ">=", $start_date)
                ->whereDate('created_at', "<=", $end_date)
                ->get();

            $detail = [
                "payment_time" => null,
                "settle_vs_open" => null,
            ];

            $sales_orders->map(function ($invoice, $key) {
                $payment_days = 0;
                //if (count($invoice->payment)) {
                $payment_days = $invoice->created_at->diffInDays(Carbon::createFromFormat('Y-m-d', Carbon::now()->format("Y-m-d")));
                //}
                $invoice->payment_days = $payment_days;
            });

            // return collect($sales_orders);
            $days0_30 = collect($sales_orders)->where("payment_days", "<=", 30)->whereIn("payment_status", ["paid", "unpaid"])->count();
            $days31_60 = collect($sales_orders)->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->whereIn("payment_status", ["paid", "unpaid"])->count();
            $days61_90 = collect($sales_orders)->where("payment_days", ">=", 61)->where("payment_days", "<=", 90)->whereIn("payment_status", ["paid", "unpaid"])->count();
            $more90_days = $sales_orders->where("payment_days", ">", 90)->where("payment_days", "<=", 365)->whereIn("payment_status", ["paid", "unpaid"])->count();
            $rumit = $sales_orders->whereIn("payment_status", ["paid", "unpaid"])->where("payment_days", ">", 365)->count();

            $days0_30_nominal = $sales_orders->where("payment_days", "<=", 30)->whereIn("payment_status", ["paid", "unpaid"])->sum("total") + $sales_orders->where("payment_days", "<=", 30)->whereIn("payment_status", ["paid", "unpaid"])->sum("ppn");
            $days31_60_nominal = $sales_orders->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->whereIn("payment_status", ["paid", "unpaid"])->sum("total") + $sales_orders->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->whereIn("payment_status", ["paid", "unpaid"])->sum("ppn");
            $days61_90_nominal = $sales_orders->where("payment_days", ">=", 61)->where("payment_days", "<=", 90)->whereIn("payment_status", ["paid", "unpaid"])->sum("total") + $sales_orders->where("payment_days", ">=", 61)->where("payment_days", "<=", 90)->whereIn("payment_status", ["paid", "unpaid"])->sum("ppn");
            $more90_days_nominal = $sales_orders->where("payment_days", ">", 90)->where("payment_days", "<=", 365)->whereIn("payment_status", ["paid", "unpaid"])->sum("total") + $sales_orders->where("payment_days", ">=", 90)->where("payment_days", "<=", 365)->whereIn("payment_status", ["paid", "unpaid"])->sum("ppn");
            $rumit_nominal = $sales_orders->whereIn("payment_status", ["paid", "unpaid"])->where("payment_days", ">", 365)->sum("total") + $sales_orders->whereIn("payment_status", ["paid", "unpaid"])->where("payment_days", ">", 365)->sum("ppn");

            $total = $days0_30 + $days31_60 + $days61_90 + $more90_days + $rumit;

            $belum_bayar = $sales_orders->where("payment_status", "unpaid")->count();
            $belum_bayar_nominal = $sales_orders->where("payment_status", "unpaid")->sum('total') + $sales_orders->where("payment_status", "unpaid")->sum('ppn');

            $belum_lunas = $sales_orders->where("payment_status", "paid")->count();
            $belum_lunas_nominal = $sales_orders->where("payment_status", "paid")->sum('total') + $sales_orders->where("payment_status", "paid")->sum('ppn');

            $totallunasvsopen = $belum_lunas + $belum_bayar;

            $belum_lunas_all = $sales_orders->whereIn("payment_status", ["paid", "unpaid"])->count();
            $belum_lunas_more_90 = $sales_orders->where("payment_days", ">", 365)->whereIn("payment_status", ["paid", "unpaid"])->count();

            $detail["payment_time"]["0_30days"]["count_invoice"] = $days0_30;
            $detail["payment_time"]["31_60days"]["count_invoice"] = $days31_60;
            $detail["payment_time"]["61_90days"]["count_invoice"] = $days61_90;
            $detail["payment_time"]["more_90_days"]["count_invoice"] = $more90_days;
            $detail["payment_time"]["rumit"]["count_invoice"] = $rumit;

            $detail["payment_time"]["0_30days"]["persentage"] = $days0_30 / ($total ?: 1) * 100;
            $detail["payment_time"]["31_60days"]["persentage"] = $days31_60 / ($total ?: 1) * 100;
            $detail["payment_time"]["61_90days"]["persentage"] = $days61_90 / ($total ?: 1) * 100;
            $detail["payment_time"]["more_90_days"]["persentage"] = $more90_days / ($total ?: 1) * 100;
            $detail["payment_time"]["rumit"]["persentage"] = $rumit / ($total ?: 1) * 100;

            $detail["payment_time"]["0_30days"]["nominal_invoice"] = $days0_30_nominal;
            $detail["payment_time"]["31_60days"]["nominal_invoice"] = $days31_60_nominal;
            $detail["payment_time"]["61_90days"]["nominal_invoice"] = $days61_90_nominal;
            $detail["payment_time"]["more_90_days"]["nominal_invoice"] = $more90_days_nominal;
            $detail["payment_time"]["rumit"]["nominal_invoice"] = $rumit_nominal;

            // $detail["payment_time"]["lebihan"]["persentage"] = $lebihan / ($total ?: 1) * 100;

            $detail["settle_vs_open"]["belum_lunas"]["count_invoice"] = $belum_lunas;
            $detail["settle_vs_open"]["belum_bayar"]["count_invoice"] = $belum_bayar;

            $detail["settle_vs_open"]["belum_lunas"]["persentage"] = $belum_lunas / ($totallunasvsopen ?: 1) * 100;
            $detail["settle_vs_open"]["belum_bayar"]["persentage"] = $belum_bayar / ($totallunasvsopen ?: 1) * 100;

            $detail["settle_vs_open"]["belum_lunas"]["nominal_invoice"] = $belum_lunas_nominal;
            $detail["settle_vs_open"]["belum_bayar"]["nominal_invoice"] = $belum_bayar_nominal;

            $detail["total_belum_lunas_payment_time"] = $total;

            $detail["total_belum_lunas_all"] = $belum_lunas_all;
            // $detail["total_belum_lunas_less_90"] = $belum_lunas_less_90;
            $detail["total_belum_lunas_more_90"] = $belum_lunas_more_90;
            // $detail["count_invoice_not_settle_out"] = $belum_lunas + $belum_lunas_less_90 + $belum_lunas_more_90;

            return $this->response("00", "Statistic Diagram Payment Time Not Settle", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get Statistic Diagram Payment Time", $th->getMessage());
        }
    }

    public function proformaTotalBaseStatus()
    {
        try {

            $thisYear = Carbon::now()->year;
            $data = [];

            $proforma = Invoice::query()->whereYear('created_at', $thisYear)->whereNull("deleted_at")->get();

            $collection_proforma = collect($proforma);

            $data['lunas'] = $collection_proforma->where("payment_status", "settle")->count();
            $data['belum_lunas'] = $collection_proforma->where("payment_status", "paid")->count();
            $data['belum_bayar'] = $collection_proforma->where("payment_status", "unpaid")->count();

            return $this->response("00", "Proforma total", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get Statistic Proforma total", $th->getMessage());
        }
    }

    public function performNominalBaseStatus()
    {
        $thisYear = Carbon::now()->year;
        $data = [];

        $proforma = Invoice::query()->whereYear('created_at', $thisYear)->whereNull("deleted_at")->get();

        $collection_proforma = collect($proforma);

        $data['lunas'] = $collection_proforma->where("payment_status", "settle")->sum('total');
        $data['belum_lunas'] = $collection_proforma->where("payment_status", "paid")->sum('total');
        $data['belum_bayar'] = $collection_proforma->where("payment_status", "unpaid")->sum('total');

        return $this->response("00", "Proforma nominal", $data);
    }

    public function pushNotification($order_type, $personel_id)
    {
        $personel = DB::table('personels')->whereNull("deleted_at")->where("id", $personel_id)->first();
        $response = Http::withToken("A1EA3D919B1A587617F85E5A260B7016B73392791AE7273332AC5D7D17D22D56")
            ->post("https://a07cbe92-c580-41ef-8564-ff65c028db2b.pushnotifications.pusher.com/publish_api/v1/instances/a07cbe92-c580-41ef-8564-ff65c028db2b/publishes", [
                "interests" => [env('APP_ENV') . "-" . $order_type],
                "web" => [
                    "notification" => [
                        "title" => "Sales Order berhasil di submit",
                        "body" => "Sales order oleh: " . $personel->name,
                    ],
                ],
                "fcm" => [
                    "notification" => [
                        "title" => "Sales Order berhasil di submit",
                        "body" => "Sales order oleh: " . $personel->name,
                    ],
                ],
            ]);
        return $response;
    }

    public function exportSalesOrder()
    {
        # code... export sales order where has is distributor
        $data = SalesOrderExport::query()->whereHas("distributor")->whereNull("deleted_at")->get();
    }

    public function performPaymentTime(Request $request)
    {

        try {

            if ($request->start_date && $request->end_date) {

                $begin = date('Y-m-d', strtotime($request->start_date));
                $end = date('Y-m-d', strtotime($request->end_date));
            } else {
                $start_date = Carbon::now();
                $begin = $start_date->copy()->startOfYear();
                $end = Carbon::now();
            }
            // return $invoice;
            $invoices = Invoice::query()
                ->with([
                    "payment" => function ($QQQ) {
                        return $QQQ
                            ->orderBy("created_at", "desc");
                    },
                ])
                ->whereHas("payment", function ($QQQ) {
                    return $QQQ
                        ->orderBy("created_at", "desc");
                })
                ->whereDate('created_at', ">=", $begin)
                ->whereDate('created_at', "<=", $end)
                ->get();

            // return $invoices;
            $invoices->map(function ($invoice, $key) {
                $payment_days = 0;
                if (count($invoice->payment)) {
                    $payment_days = $invoice->created_at->diffInDays($invoice->payment[0]->created_at);
                }
                $invoice->payment_days = $payment_days;
            });

            $invoice_group_by_payment_days = [];
            $invoice_group_by_payment_days["payment_time"]["0_30days"]["count_invoice"] = $invoices->where("payment_days", "<=", 30)->where("payment_status", "settle")->count();
            $invoice_group_by_payment_days["payment_time"]["31_60days"]["count_invoice"] = $invoices->where("payment_days", ">=", 31)->where("payment_days", "<=", 60)->where("payment_status", "settle")->count();
            $invoice_group_by_payment_days["payment_time"]["61_90days"]["count_invoice"] = $invoices->where("payment_days", ">=", 61)->where("payment_days", "<", 90)->where("payment_status", "settle")->count();
            $invoice_group_by_payment_days["payment_time"]["more_90_days"]["count_invoice"] = $invoices->where("payment_days", ">=", 90)->where("payment_status", "settle")->count();

            $invoice_group_by_payment_days["payment_time"]["lebihan"]["count_invoice"] = $invoices->where("remaining", "<", 0)->Where("payment_status", "settle")->count();
            $invoice_group_by_payment_days["payment_time"]["rumit"]["count_invoice"] = $invoices->whereIn("payment_status", ["paid", "unpaid"])->where("payment_days", ">=", 90)->count();
            return $this->response("00", " success to get Statistic Diagram Payment Time", $invoice_group_by_payment_days);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get Statistic Proforma total", $th->getMessage());
        }
    }

    public function pointOrigin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "personel_id" => "required",
        ]);

        $request->merge([
            "year" => $request->has("year") ? $request->year : now()->format("Y"),
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        try {
            ini_set('max_execution_time', 1500); //3 minutes

            /**
             * point marketing product references
             */
            $list_product_a_b = DB::table('point_products')
                ->whereNull("deleted_at")
                ->where("year", $request->year)
                ->select('product_id')
                ->get()
                ->pluck('product_id')
                ->toArray();

            $personel_id = $request->personel_id ? $request->personel_id : auth()->user()->personel_id;
            $sales_order = $this->sales_order->query()
                ->with([
                    "invoice",
                    'personel',
                    'dealer.adress_detail',
                    'subDealer.adressDetail',
                    'distributor.ditributorContract',
                    'sales_order_detail' => function ($query) use ($list_product_a_b) {
                        return $query
                            ->whereNotNull("marketing_point")
                            ->whereIn('product_id', array_unique($list_product_a_b));
                    },
                ])
                ->whereNull('return')
                ->where('status', "confirmed")
                ->where(
                    function ($Q) {
                        return $Q
                            ->whereHas('dealer', function ($QQQ) {
                                return $QQQ->withTrashed();
                            })
                            ->orWhereHas('subDealer', function ($QQQ) {
                                return $QQQ->withTrashed();
                            });
                    }
                )
                ->confirmedOrderByYear($request->year)
                ->when($request->personel_id, function ($QQQ) use ($request) {
                    return $QQQ->where("personel_id", $request->personel_id);
                })
                ->whereHas('logWorkerPointMarketing')
                ->whereHas('sales_order_detail')
                ->orderBy(($request->sort_by ? $request->sort_by : "created_at"), ($request->direction ? $request->direction : "asc"));

            if ($request->disabled_pagination) {
                $sales_order = $sales_order->get();
            } else {
                $sales_order = $sales_order->paginate($request->limit ? $request->limit : 15);
            }

            foreach ($sales_order as $key => $value) {
                $value["poin_total"] = $value->sales_order_detail->sum("marketing_point");
            }

            return $this->response("00", " success to get data point Origin", $sales_order);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get data point Origin", [
                "messsage" => $th->getMessage(),
                "file" => $th->getFile(),
                "line" => $th->getLine(),
            ]);
        }
    }

    /**
     * marketing achievement target group by region on graphic
     *
     * @param Request $request
     * @return void
     */
    public function listProformaByRegionPerYear(Request $request)
    {
        try {
            $fiveYearsAgo = Carbon::now()->subYears(4);
            $test = collect();

            $invoice_temps = collect();
            $regions = DB::table('invoices as i')
                ->whereNull("i.deleted_at")
                ->join("sales_orders as s", "s.id", "i.sales_order_id")
                ->orderBy("i.id")
                ->Select("i.total", "i.created_at", "s.store_id")
                ->where("i.created_at", ">", $fiveYearsAgo->startOfYear()->startOfDay()->format("Y-m-d h:m:s"))
                ->chunk(100, function ($regions) use (&$invoice_temps) {
                    $invoice_temps->push($regions);
                });

            $store_region_temps = collect();
            $store_regions = DB::table('view_store_region')
                ->orderBy("region")
                ->where("type", "dealer")
                ->whereNotNull("cust")
                ->chunk(100, function ($store_regions) use (&$store_region_temps) {
                    $store_region_temps->push($store_regions);
                });

            /**
             * region list
             */
            $region_list = DB::table('marketing_area_regions as mr')
                ->whereNull("deleted_at")
                ->get();

            $invoice_district = $invoice_temps
                ->flatten()

                /* if region null set to kantor */
                ->map(function ($invoice) use ($store_region_temps) {
                    $invoice_region = collect($store_region_temps->flatten()->toArray())->where("store_id", $invoice->store_id)->first();
                    if ($invoice_region) {
                        if ($invoice_region->region_id) {
                            $invoice->region_id = $invoice_region->region_id;
                        } else {
                            $region = ["id" => "kantor", "name" => "kantor"];
                            $invoice->region_id = "kantor";
                        }
                    } else {
                        $invoice->region_id = "kantor";
                    }
                    return $invoice;
                })
                ->sortBy("created_at")
                ->groupBy([
                    function ($invoice) {
                        return $invoice->region_id;
                    },
                    function ($invoice) {
                        return Carbon::parse($invoice->created_at)->format("Y");
                    },
                ])

                /* sum per sub per year  */
                ->map(function ($order_sub, $region_id) use ($region_list) {
                    $recap_year = collect();
                    for ($i = 4; $i >= 0; $i--) {
                        $recap_year[now()->subYears($i)->format("Y")] = 0;
                    }

                    $order_sub = collect($order_sub)->map(function ($order_year, $year) use (&$region, &$recap_year) {
                        return $order_year->sum("total");
                    });

                    $test = collect($recap_year)
                        ->map(function ($recap, $year) use ($recap_year, &$order_sub) {
                            if (!in_array($year, $order_sub->keys()->toArray())) {
                                $order_sub[$year] = 0;
                            }

                            return $year;
                        })
                        ->sortKeys();

                    $region_list = $region_list->where("id", $region_id)->first();
                    $order_sub = $order_sub->sortKeys();

                    if ($region_list) {
                        $order_sub["region"] = $region_list;
                    } else {
                        $order_sub["region"] = ["id" => "kantor", "name" => "kantor", "target" => 0];
                    }
                    return $order_sub;
                });

            return $this->response("00", "success to perform by region and by year", $invoice_district);

            $sales_orders = DB::table('sales_orders as s')
                ->join("invoices as i", "i.sales_order_id", "=", "s.id")
                ->join("dealers as d", "d.id", "=", "s.store_id")
                ->whereNull("s.deleted_at")
                ->whereNull("i.deleted_at")
                ->whereNull("d.deleted_at")
                ->whereIn("s.status", ["confirmed", "returned", "pending"])
                ->where("s.type", "1")
                ->whereYear('i.created_at', ">", $fiveYearsAgo)
                ->select("i.total", "i.created_at as confirmed_date", "s.store_id", "s.id as s_id")
                ->groupBy("i.id")
                ->orderBy("s.date")
                ->chunk(100, function ($orders) use (&$test) {
                    $test->push($orders);
                });

            $area_district = DB::table('marketing_area_districts as md')
                ->join("address_with_details as ad", "md.district_id", "=", "ad.district_id")
                ->join("marketing_area_sub_regions as ms", "ms.id", "=", "md.sub_region_id")
                ->join("marketing_area_regions as mr", "mr.id", "=", "ms.region_id")
                ->whereIn("ad.parent_id", $test->flatten()->pluck("store_id"))
                ->where("ad.type", "dealer")
                ->whereNull("ad.deleted_at")
                ->whereNull("md.deleted_at")
                ->whereNull("ms.deleted_at")
                ->whereNull("mr.deleted_at")
                ->select("ad.parent_id", "mr.*")
                ->get();

            $sales_orders = collect($test->flatten())
                ->map(function ($order) use ($area_district) {
                    $region_order = $area_district->where("parent_id", $order->store_id)->first();

                    if ($region_order) {
                        $order->region = $region_order;
                    } else {
                        $order->region = null;
                    }

                    return $order;
                })
                ->filter(function ($order) {
                    if ($order->region) {
                        return $order;
                    }
                });

            $dealer_in_region = [];

            $data = [];

            $new_data = [];
            $start_date_year = Carbon::now()->subYears(4)->startOfYear();
            $periodyear = CarbonPeriod::create($start_date_year, Carbon::now()->endOfYear())->month();

            $regions = Region::query()
                ->get()
                ->map(function ($data, $key) use (&$new_data, $periodyear) {
                    $new_data[$data->id]["region"] = $data;
                    foreach ($periodyear as $years) {
                        $new_data[$data->id][$years->format('Y')] = 0;
                    }
                    return $new_data;
                });

            /* get all marketing on region if request has region_id */
            if ($request->has("region_id")) {
                $dealer_in_region = $this->dealerListByArea($request->region_id);
                $new_data = collect($new_data)
                    ->filter(function ($region, $region_id) use ($request) {
                        if ($region_id == $request->region_id) {
                            return $region;
                        }
                    })
                    ->toArray();
            }

            /* group data bt region */
            $sales_orders_grouped = $sales_orders->flatten()->groupBy([
                function ($val) {
                    return $val->region->id;
                },
                function ($val) {
                    return Carbon::parse($val->confirmed_date)->format('Y');
                },
            ]);

            // 1. group by wilayah
            // 2. setiap wilayah punya 4 tahun
            //
            foreach ($sales_orders_grouped as $area => $value) {
                for ($i = 4; $i >= 0; $i--) {
                    $new_data[$area]["region"] = null;
                    $new_data[$area][Carbon::now()->subYears($i)->format("Y")] = 0;
                }
            }

            // 3. memasukkan kekosongan
            foreach ($sales_orders_grouped as $area => $value) {
                foreach ($value as $year => $val) {
                    $region = $val[0]->region;
                    $new_data[$area]["region"] = $region;
                    $new_data[$area][$year] = collect($val)->sum('total');
                }
            }

            return $this->response("00", "success to perform by region and by year", $new_data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing achievement target recap", $th->getMessage());
        }
    }

    public function listChartProformaByRegionThreeYear()
    {

        try {
            ini_set('max_execution_time', 1500); //3 minutes
            $district_on_region = null;
            $personel_on_district = [];

            $data = [];

            /* get all marketing on region if request has region_id */
            $threeYearsAgo = Carbon::now()->subYears(2);

            /* invoice three years */
            $invoice_temps = collect();
            $invoices = DB::table('invoices as i')
                ->join("sales_orders as s", "s.id", "i.sales_order_id")
                ->where("i.created_at", ">=", $threeYearsAgo->startOfYear()->startOfDay())
                ->whereNull("i.deleted_at")
                ->select("i.*", "s.store_id")
                ->orderBy("i.created_at")
                ->chunk(100, function ($invoices) use ($invoice_temps) {
                    $invoice_temps->push($invoices);
                });

            /**
             * store region
             */
            $store_region_temps = collect();
            $store_regions = DB::table('view_store_region')
                ->orderBy("region")
                ->where("type", "dealer")
                ->whereNotNull("cust")
                ->chunk(100, function ($store_regions) use (&$store_region_temps) {
                    $store_region_temps->push($store_regions);
                });

            /**
             * region list
             */
            $region_list = DB::table('marketing_area_regions as mr')
                ->whereNull("deleted_at")
                ->get();

            $invoice_district = $invoice_temps
                ->flatten()

                /* if region null set to kantor */
                ->map(function ($invoice) use ($store_region_temps) {
                    $invoice_region = collect($store_region_temps->flatten()->toArray())->where("store_id", $invoice->store_id)->first();
                    if ($invoice_region) {
                        if ($invoice_region->region_id) {
                            $invoice->region_id = $invoice_region->region_id;
                        } else {
                            $region = ["id" => "kantor", "name" => "kantor"];
                            $invoice->region_id = "kantor";
                        }
                    } else {
                        $invoice->region_id = "kantor";
                    }
                    return $invoice;
                })
                ->sortBy("created_at")
                ->groupBy([
                    function ($invoice) {
                        return Carbon::parse($invoice->created_at)->format("Y");
                    },
                    function ($invoice) {
                        return $invoice->region_id;
                    },
                ])
                ->map(function ($invoice_per_year, $year) use ($region_list) {

                    $detail = collect();

                    $total_per_year = $invoice_per_year->flatten()->sum("total");

                    $invoice_per_year = collect($invoice_per_year)->map(function ($invoice_per_sub, $region_id) use (&$detail, $region_list, &$total_per_year) {
                        $region = collect($region_list)->where("id", $region_id)->first();

                        if ($region) {
                            $detail[$region_id] = [
                                "name" => $region->name,
                                "dataLabels" => (object) [
                                    "format" => $region->name . " + " . collect($invoice_per_sub)->sum("total") / $total_per_year * 100 . " + %",
                                ],
                                "persentase" => collect($invoice_per_sub)->sum("total") / $total_per_year * 100,
                            ];
                        } else {
                            $detail["kantor"] = [
                                "name" => "kantor",
                                "dataLabels" => (object) [
                                    "format" => "kantor" . " + " . collect($invoice_per_sub)->sum("total") / $total_per_year * 100 . " + %",
                                ],
                                "persentase" => collect($invoice_per_sub)->sum("total") / $total_per_year * 100,
                            ];
                        }
                    });

                    $detail_year = [
                        "title" => $year,
                        "data" => $detail,
                        "subtitle" => $total_per_year,
                    ];

                    return $detail_year;
                });

            for ($i = 0; $i < 3; $i++) {
                if (!in_array(now()->subYears($i)->format("Y"), $invoice_district->keys()->toArray())) {
                    $invoice_district[now()->subYears($i)->format("Y")] = [
                        "title" => now()->subYears($i)->format("Y"),
                        "data" => null,
                        "subtitle" => null,
                    ];
                }
            }

            return $this->response("00", "success to get data perform by region and by year", $invoice_district->sortKeys());

            // $sales_orders = $this->sales_order->query()
            //     ->whereYear('created_at', ">", $threeYearsAgo)
            //     ->where("type", 1)
            // /* filter region */

            //     ->with([
            //         "personel" => function ($QQQ) {
            //             return $QQQ->with([
            //                 "areaMarketing" => function ($Q) {
            //                     return $Q->with([
            //                         "subRegionWithRegion" => function ($Q) {
            //                             return $Q->with([
            //                                 "region",
            //                             ]);
            //                         },
            //                     ]);
            //                 },
            //             ])
            //                 ->whereHas("areaMarketing");
            //         },
            //         "invoice",
            //         "sales_order_detail",
            //     ])

            //     ->whereHas("personel", function ($QQQ) {
            //         return $QQQ->whereHas("areaMarketing");
            //     })
            //     ->whereHas("invoice")
            //     ->where("status", "confirmed")
            //     ->get();

            // /* group data bt region */
            // $sales_orders_grouped = $sales_orders->groupBy([
            //     function ($val) {
            //         return $val->created_at->format('Y');
            //     },
            //     function ($val) {
            //         return $val->personel->areaMarketing->subRegionWithRegion->region->id;
            //     },
            // ]);

            // // return $sales_orders_grouped;
            // // 1. tahunnya yang diatas (grup by region berdasarkan tahun)

            // //
            // foreach ($sales_orders_grouped as $year => $value) {
            //     for ($i = 2; $i >= 0; $i--) {
            //         $data[Carbon::now()->subYears($i)->format("Y")]["title"] = null;
            //         $data[Carbon::now()->subYears($i)->format("Y")]["data"] = null;
            //         $data[Carbon::now()->subYears($i)->format("Y")]["subtitle"] = 0;
            //         // $data[$year] = null;
            //         // $data['data'] = null;
            //     }
            // }

            // // return $data;

            // // $detail = [];
            // // 3. memasukkan kekosongan
            // foreach ($sales_orders_grouped as $year => $value) {
            //     $count_total = 0;
            //     foreach ($value as $area => $val) {
            //         $count_total += $val->sum('invoice.total');
            //         // $persentage = $val[0]->
            //         $array_data[$area]["dataLabels"] = null;
            //     }
            //     $array_data = [];
            //     foreach ($value as $area => $val) {
            //         $array_data[$area]["name"] = $val[0]->personel->areaMarketing->subRegionWithRegion->region->name;

            //         $array_data[$area]["dataLabels"] = [
            //             "format" => $array_data[$area]["name"] . " + " . collect($val)->sum('invoice.total') / $count_total * 100 . " + " . '%',
            //         ];
            //         $array_data[$area]["persentase"] = collect($val)->sum('invoice.total') / $count_total * 100;
            //     }
            //     $data[$year]["title"] = $year;
            //     $data[$year]["subtitle"] = $count_total;
            //     $data[$year]["data"] = $array_data;
            // }

            // return $this->response("00", "success to get data perform by region and by year", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get data perform by region and by year", $th->getMessage());
        }
    }
}
