<?php

namespace Modules\Invoice\Http\Controllers;


use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Invoice\Entities\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceDueDateController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected Invoice $invoice
    ) {
    }

    public function __invoke(Request $request)
    {
        $data = $this->invoice->query()
            ->paymentDue()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        }
                    ])  ->with("personel.position")
                        ->with("paymentMethod");
                },
            ])
            ->when($request->has("marketing_or_dealer_name"),function($query) use ($request){
                return $query->byDealerNameAndbyPersonel($request->marketing_or_dealer_name);
            })
            ->whereHas("salesOrder", function ($query) {
                return $query->whereHas("paymentMethod");
            })
            ->when($request->has("paymentDue"), function ($QQQ) use ($request) {
                return $QQQ->where("payment_status", "!=", "settle");
            })->get()->map(function ($value) {
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

                $value->unsetRelation("dispatchOrder");
                $value->payment_method_days = $payment_method_days;
                $value->payment_due_date = $payment_due_date;
                $value->diff_days = Carbon::parse($due_date2)->startOfDay()->diffInDays(Carbon::now()->startOfDay()->format('Y-m-d H:i:s'), false);
                return $value;
            })->sortBy("payment_due_date")->filter(function ($item) use ($request) {
                if($request->payment_due_date){
                    return Carbon::parse($item->payment_due_date)->isSameDay(Carbon::createFromFormat('Y-m-d', $request->payment_due_date));
                } 
                return $item;
            });

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageLimit = $request->limit > 0 ? $request->limit : 10;
        $currentItems = collect($data)
            ->slice($pageLimit * ($currentPage - 1), $pageLimit)
            ->values();
        $path = LengthAwarePaginator::resolveCurrentPath();
        $paginator = new LengthAwarePaginator($currentItems, count($data), $pageLimit, $currentPage, ['path' => $path]);
        return $this->response('00', 'success, get data invoice payment due date', $paginator);
    }
}
