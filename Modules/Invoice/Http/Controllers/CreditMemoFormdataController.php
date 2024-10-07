<?php

namespace Modules\Invoice\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Transformers\CreditMemoFormDataResource;
use Modules\SalesOrder\Entities\SalesOrder;

class CreditMemoFormdataController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SalesOrder $sales_order,
        protected Invoice $invoice,
    ) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            "invoice_id" => "required|max:40",
        ]);

        $invoice = $this->invoice->query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "dealerV2" => function ($QQQ) {
                            return $QQQ->with([
                                "agencyLevel",
                                "addressDetail" => function ($QQQ) {
                                    return $QQQ
                                        ->with([
                                            "district",
                                            "city",
                                            "province",
                                        ])
                                        ->where("type", "dealer");
                                },
                            ]);
                        },
                        "salesOrderDetail" => function ($QQQ) {
                            return $QQQ->with([
                                "product" => function ($QQQ) {
                                    return $QQQ->with([
                                        "categoryProduct",
                                    ]);
                                },
                            ]);
                        },
                    ]);
                },
                "creditMemos" => function ($QQQ) {
                    return $QQQ->with([
                        "creditMemoDetail",
                    ]);
                },
            ])
            ->findOrFail($request->invoice_id);

        try {
            $proforma_destination = DB::table('invoices as i')
                ->join("sales_orders as s", "s.id", "i.sales_order_id")
                ->whereNull("i.deleted_at")
                ->whereNull("s.deleted_at")
                ->where("s.store_id", $invoice->salesOrder->store_id)
                ->where(function ($QQQ) use ($invoice) {
                    return $QQQ
                        ->where("i.payment_status", "!=", "settle")
                        ->orWhere("i.id", $invoice->id);
                })
                ->when($invoice->salesOrder->status == "returned", function ($QQQ) use ($invoice) {
                    return $QQQ->where("i.id", $invoice->id);
                })
                ->when(!in_array($invoice->delivery_status, [1, 3]), function($QQQ){
                    return $QQQ->whereNull("i.id");
                })
                ->limit(25)
                ->select("i.id", "i.invoice")
                ->orderBy("i.invoice")
                ->get();

            return new CreditMemoFormDataResource([
                "invoice" => $invoice,
                "proforma_destination" => $proforma_destination,
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
