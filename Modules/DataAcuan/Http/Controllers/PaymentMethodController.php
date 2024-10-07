<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Exports\PaymentMethodExport;
use App\Traits\ResponseHandlerV2;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;

class PaymentMethodController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(PaymentMethod $payment_method)
    {
        $this->payment_method = $payment_method;
        // $this->middleware('role:Marketing Support|administrator|super-admin|admin')->except("index", "show");
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $request->validate([
            "sales_order_id" => "required_with:dealer_id"
        ]);

        try {
            $payment_methods = $this->payment_method->query()
            ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                $sort_type = "asc";
                if ($request->has("order_type")) {
                    $sort_type = $request->order_type;
                }
                if ($request->sorting_column == 'name') {
                    return $QQQ->orderBy('name', $request->order_type);
                } elseif ($request->sorting_column == 'days') {
                    return $QQQ->orderBy('days', $sort_type);
                } else {
                    return $QQQ->orderBy("updated_at", "desc");
                }
            })

                /**
                 * according new direct sales rule this
                 * rule ha no longer applies
                 * (2023-12-04)
                 */
                ->when($request->has("dealer_has_unsettle_order"), function ($QQQ) use ($request) {
                    return $QQQ->dealerPaymentCheck($request->dealer_has_unsettle_order);
                })

                ->when($request->has("name"), function ($QQQ) use ($request) {
                    return $QQQ->where(function ($QQQ) use ($request) {
                        foreach ($request->name as $name) {
                            $QQQ = $QQQ->orWhere("name", "like", "%" . $name . "%");
                        }
                    });
                })

                /* filter payment method for marketing */
                ->when($request->has("is_for_marketing"), function ($QQQ) use ($request) {
                    return $QQQ->where("is_for_marketing", $request->is_for_marketing);
                })

                /* filter payment method according grading and dealer payment */
                ->when($request->has("dealer_id"), function ($QQQ) use ($request) {
                    return $QQQ->paymentAccordingGradeAndDealer($request->dealer_id, $request->sales_order_id);
                })

                ->get();

            return $this->response('00', 'payment method index', $payment_methods);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display peyment methods', $th);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('dataacuan::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $payment_method = $this->payment_method->fill($request->all());
            $payment_method->save();

            return $this->response("00", "payment method saved", $payment_method);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save payment method", $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $payment_method = $this->payment_method->finOrFail($id);
            return $this->response("00", "succes to display payment method", $payment_method);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to display payment method", $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('dataacuan::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $payment_method = $this->payment_method->findOrFail($id);
            $payment_method->name = $request->name;
            $payment_method->days = $request->days;
            $payment_method->save();

            return $this->response('00', 'payment method updated', $payment_method);
        } catch (\Throwable $th) {
            return $this->response('00', 'failed to update peyment methods', $th);
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
            $payment_method = $this->payment_method->findOrFail($id);
            $payment_method->delete();
            return $this->response("00", "succes to delete payment method", $payment_method);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to delete payment method", $th->getMessage());
        }
    }

    public function export()
    {
        try {
            $data = (new PaymentMethodExport)->store('payment_methods.xlsx', 's3');
            return $this->response("00", "export succes", $data);
        } catch (\Throwable $th) {
            return $this->response("00", "export succes", $th->getMessage());
        }
    }

    public function paymentMethodBaseOnCreditLimit()
    {
        try {
            $payment_methods = $this->payment_method->query()
                ->orderBy('name')
                ->when($request->has("dealer_has_unsettle_order"), function ($QQQ) use ($request) {
                    return $QQQ->dealerPaymentCheck($request->dealer_has_unsettle_order);
                })
                ->get();
            return $this->response('00', 'payment method index', $payment_methods);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display peyment methods', $th->getMessage());
        }
    }

    /**
     * scope payment method for dealer on order base on credit limit
     * if there still credit limit, all method are available
     * if there has no credit limit exit, only cash
     * method available
     *
     * @param [type] $query
     * @param [type] $dealer_id
     * @return void
     */
    public function dealerPaymentCreditLimitBased(Request $request)
    {
        try {
            $dealer = Dealer::query()
                ->with("grading", "dealerGrading", "dealerWithPayment")
                ->where("id", $request->dealer_id)
                ->whereHas("grading")
                ->firstOrFail();

            $credit_limit = count($dealer->dealerGrading) > 0
                ? ($dealer->dealerGrading[0]->custom_credit_limit
                    ? $dealer->dealerGrading[0]->custom_credit_limit
                    : $dealer->grading->credit_limit)
                : $dealer->grading->credit_limit;

            /* count total order unsettle */
            $order = SalesOrder::query()
                ->with([
                    "invoice",
                ])
                ->where("store_id", $request->dealer_id)
                ->where(function ($QQQ) {
                    return $QQQ
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->where("status", "confirmed")
                                ->whereHas("invoice", function ($QQQ) {
                                    return $QQQ
                                        ->where("payment_status", "!=", "settle")
                                        ->orderBy("created_at", "desc");
                                });
                        });
                })
                ->get();

            $total_order_submited = collect($order)->where("status", "submited")->sum("total");
            $total_order_confirmed = collect($order)
                ->where("status", "confirmed")
                ->sum("invoice.total");

            $cash = DB::table('payment_methods')->whereNull("deleted_at")->where("name", "cash")->get();
            $dealer_Payment = collect($dealer->dealerWithPayment)->pluck("payment_method_id")->toArray();

            $dealer_Payment = DB::table('payment_methods')
                ->whereNull("deleted_at")
                ->when(true, function ($Q) use ($dealer_Payment) {
                    if (count($dealer_Payment) > 0) {
                        return $Q->whereIn("id", $dealer_Payment);
                    } else {
                        return $Q;
                    }
                })
                ->get();
            $payment_methods = ($total_order_submited + $total_order_confirmed) < $credit_limit ? $dealer_Payment : $cash;
            $data = [
                "credit_limit" => $credit_limit,
                "total_order_unsettle" => $total_order_submited + $total_order_confirmed,
                "payment_methods" => $dealer_Payment,
            ];

            return $this->response("00", "success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }
}
