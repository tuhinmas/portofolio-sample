<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Modules\Analysis\Entities\DealerOrderRecapPerMonth;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\InvoiceProforma;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;

class HistoryDirectSalesController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SalesOrderHistoryChangeStatus $sales_order_history_change,
        protected Invoice $invoice,
        protected DeliveryOrder $deliveryOrder,
        protected ReceivingGood $receivingGood,
        protected InvoiceProforma $invoiceProforma,
        protected DispatchOrder $dispatchOrder
    ) {
    }

    public function __invoke(Request $request)
    {
        try {

            $data = collect();
            $this->invoice
                ->where("sales_order_id", $request->sales_order_id)
                ->whereHas("salesOrder",function($query){
                    return $query->whereNotNull("submited_at");
                })
                ->with("salesOrder")
                ->get()->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Direct Order Dibuat",
                        "created_at" => Carbon::parse($val->salesOrder->created_at)->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->salesOrder->submited_at)->format("Y-m-d H:i:s"),
                        "text_left" => [
                            "value_1" => "Dengan no. Order",
                            "value_1_is_bold" => false,
                            "value_2" => null,
                            "value_2_is_bold" => false,
                        ],
                        "text_right" => [
                            "value" => $val->salesOrder->order_number,
                            "is_bold" => true
                        ],
                    ]);
                });

            $this->invoice
                ->where("sales_order_id", $request->sales_order_id)
                ->whereHas("salesOrder", function ($query) {
                    return $query->where("status", "confirmed");
                })
                ->with("invoiceProforma")
                ->with("salesOrder.invoice")
                ->get()->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Direct Order Dikonfirmasi",
                        "created_at" => Carbon::parse($val->created_at)->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->created_at)->format("Y-m-d H:i:s"),
                        "text_left" => [
                            "value_1" => "Dengan no. Proforma",
                            "value_1_is_bold" => false,
                            "value_2" => null,
                            "value_2_is_bold" => false,
                        ],
                        "text_right" => [
                            "value" => $val->invoice,
                            "is_bold" => true
                        ],
                    ]);
                });

            $this->dispatchOrder
                ->whereHas("invoice", function ($query) use ($request) {
                    return $query->where("sales_order_id", $request->sales_order_id);
                })
                ->whereHas("deliveryOrder", function ($query) {
                    return $query->where("status", "send");
                })
                ->with("deliveryOrder", function ($query) {
                    return $query->where("status", "send");
                })
                ->with("invoice")
                ->get()
                ->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Surat Jalan Diterbitkan",
                        "created_at" => Carbon::parse($val->deliveryOrder?->created_at)->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->deliveryOrder?->created_at)->format("Y-m-d H:i:s"),
                        "text_left" => [
                            "value_1" => "Dengan no. Surat Jalan",
                            "value_1_is_bold" => false,
                            "value_2" => null,
                            "value_2_is_bold" => false,
                        ],
                        "text_right" => [
                            "value" => $val->deliveryOrder?->delivery_order_number,
                            "is_bold" => true
                        ],
                    ]);
                });


            $this->dispatchOrder
                ->whereHas("invoice", function ($query) use ($request) {
                    return $query->where("sales_order_id", $request->sales_order_id);
                })
                ->whereHas("deliveryOrder", function ($query) {
                    return $query->where("status", "send")->where("date_delivery", "<=", Carbon::now()->format("Y-m-d"));
                })
                ->with("deliveryOrder", function ($query) {
                    return $query->where("status", "send");
                })
                ->with("invoice")
                ->get()
                ->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Surat Jalan " . $val->deliveryOrder?->delivery_order_number . " Dikirimkan",
                        "created_at" => Carbon::parse($val->deliveryOrder?->created_at)->addSecond()->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->deliveryOrder?->date_delivery)->format("Y-m-d H:i:s"),
                        "text_left" => [
                            "value_1" => "Oleh",
                            "value_1_is_bold" => false,
                            "value_2" => $val->deliveryOrder?->createdBy?->name,
                            "value_2_is_bold" => true,
                        ],
                        "text_right" => [
                            "value" => $val->deliveryOrder?->createdBy?->position->name,
                            "is_bold" => false
                        ],
                    ]);
                });

            $this->dispatchOrder
                ->whereHas("invoice", function ($query) use ($request) {
                    return 
                    $query
                    // ->whereIn("delivery_status", ["1", "3"])
                    ->where("sales_order_id", $request->sales_order_id);
                })
                ->whereHas("deliveryOrder", function ($query) {
                    return $query->where("status", "send")
                        ->where("date_delivery", "<=", Carbon::now()->format("Y-m-d"))
                        ->whereHas("receivingGoods");
                })
                ->with("deliveryOrder", function ($query) {
                    return $query->where("status", "send")
                        ->with("receivingGoods.receivedBy.position");
                })
                ->with("invoice")
                ->get()
                ->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Surat Jalan " . $val->deliveryOrder?->delivery_order_number . " Diterima",
                        "created_at" => Carbon::parse($val->deliveryOrder?->receivingGoods?->created_at)->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->deliveryOrder?->receivingGoods?->updated_at)->format("Y-m-d H:i:s"),
                        "text_left" => [
                            "value_1" => "Oleh",
                            "value_1_is_bold" => false,
                            "value_2" => $val->deliveryOrder?->receivingGoods?->receivedBy?->name,
                            "value_2_is_bold" => true,
                        ],
                        "text_right" => [
                            "value" => $val->deliveryOrder?->receivingGoods?->receivedBy?->position->name,
                            "is_bold" => false
                        ],

                    ]);
                });


            $this->invoice
                ->where("sales_order_id", $request->sales_order_id)
                ->with("salesOrder.invoice")
                ->with("invoiceProforma")
                ->whereHas("invoiceProforma")
                ->get()
                ->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Invoice Diterbitkan",
                        "created_at" => Carbon::parse($val->invoiceProforma?->created_at)->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->invoiceProforma?->created_at)->format("Y-m-d"),
                        "text_left" => [
                            "value_1" => "Dengan No. Invoice",
                            "value_1_is_bold" => false,
                            "value_2" => null,
                            "value_2_is_bold" => false,
                        ],
                        "text_right" => [
                            "value" => $val->invoiceProforma?->invoice_proforma_number,
                            "is_bold" => true
                        ],

                    ]);
                });

            $this->invoice
                ->where("sales_order_id", $request->sales_order_id)
                ->with("firstDeliveryOrder")
                ->where("delivery_status", "1")
                ->get()
                ->map(function ($val, $key) use (&$data) {
                    $data->push([
                        "title" => "Pengiriman Selesai",
                        "created_at" => Carbon::parse($val->lastReceivingGood?->updated_at)->format("Y-m-d H:i:s"),
                        "date" => Carbon::parse($val->lastReceivingGood?->updated_at)->addMinute()->format("Y-m-d H:i:s"),
                        "text_left" => [
                            "value_1" => null,
                            "value_1_is_bold" => false,
                            "value_2" => null,
                            "value_2_is_bold" => false,
                        ],
                        "text_right" => [
                            "value" => null,
                            "is_bold" => false
                        ],

                    ]);
                });


            $final_data = $data->sortByDesc("date")->values();
            if ($request->sorting_column == "date") {
                if ($request->direction == "asc") {
                    $final_data = $data->sortBy("date")->values();
                }
            } elseif($request->sorting_column == "created_at"){
                $final_data = $data->sortByDesc("created_at")->values();
                if ($request->direction == "asc") {
                    $final_data = $data->sortBy("created_at")->values();
                }
            }

            return $this->response('00', 'success', $final_data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
