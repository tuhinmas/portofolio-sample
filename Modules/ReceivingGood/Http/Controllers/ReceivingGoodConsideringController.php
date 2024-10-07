<?php

namespace Modules\ReceivingGood\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\DistributionChannel\Actions\GetProductDispatchAction;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\ReceivingGood\Entities\ReceivingGoodReceived;
use Modules\ReceivingGood\Events\DeliveryStatusCheckInReceivingGoodEvent;
use Modules\ReceivingGood\Events\DipatchOnReceivedEvent;
use Modules\ReceivingGood\Jobs\NotificationReceivingGoodJob;

class ReceivingGoodConsideringController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected ReceivingGoodReceived $receiving_goog_received,
        protected ReceivingGoodDetail $receiving_good_detail,
        protected ReceivingGood $receiving_good,
        protected DeliveryOrder $delivery_order,
    ) {}

    public function __invoke(Request $request, GetProductDispatchAction $product_dispatch)
    {
        $delivery_order = DeliveryOrder::findOrFail($request->delivery_order_id);

        $request->validate([
            "delivery_order_id" => [
                "required",
                Rule::unique('receiving_goods')->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->where('delivery_order_id', $request->delivery_order_id)
                        ->whereNull("deleted_at")
                        ->where("delivery_status", "2");
                }),
            ],
            "date_received" => "required|date",
            "products" => "required|array",
            "products.*.product_id" => ((bool) !$delivery_order->dispatch_promotion_id ? 'required|string' : 'nullable'),
            "products.*.quantity" => "required",
            "products.*.quantity_package" => "nullable",
            "products.*.status" => [
                "required",
                Rule::in("delivered", "broken", "incorrect", ),
            ],
            "products.*.promotion_good_id" => ((bool) $delivery_order->dispatch_promotion_id ? 'required|string' : 'nullable'),
        ], [
            "delivery_order_id.unique" => "surat jalan sudah diterima",
            "products.*.status.in" => "status hanya delivered, broken, incorrect",
        ]);

        $product_dispatches = $product_dispatch($request->delivery_order_id);

        /* validating data */
        self::validateData($request, $product_dispatches);

        try {

            DB::beginTransaction();
            $receiving_good = $this->receiving_good->create([
                "delivery_order_id" => $request->delivery_order_id,
                "date_received" => $request->date_received,
                "received_by" => auth()->user()->personel_id,
                "delivery_status" => "2",
                "note" => "considered receiving good",
            ]);

            $products = collect($request->products)
                ->map(function ($product) {
                    $product["user_id"] = auth()->id();
                    return $product;
                })
                ->toArray();

            $receiving_good->receivingGoodDetail()->createMany($products);
            $receiving_good->load("receivingGoodDetail");

            self::afterReceive($receiving_good);
            DB::commit();
            return $this->response("00", "success", $receiving_good);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed", $th);
        }
    }

    public function validateData($request, $product_dispatches)
    {
        $group_by = "product_id";
        $is_data_valid = collect($request->products)
            ->groupBy(function ($product) use (&$group_by) {
                if (isset($product["product_id"]) && !empty($product["product_id"])) {
                    return $product["product_id"];
                }

                $group_by = "promotion_good_id";
                return $product["promotion_good_id"];
            })
            ->filter(fn($product) => in_array($product->first()["$group_by"], $product_dispatches->pluck("$group_by")->toArray()))
            ->count() == $product_dispatches->count();

        if (!$is_data_valid) {
            $response = $this->response("04", "invalid data send", [
                "products" => [
                    "produk tidak sesuai muatan",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        $is_data_valid = collect($request->products)
            ->groupBy(function ($product) {
                if (isset($product["product_id"]) && !empty($product["product_id"])) {
                    return $product["product_id"];
                }

                return $product["promotion_good_id"];
            })
            ->filter(function ($product_group) {
                return $product_group->unique("status")->filter(fn($product) => in_array($product["status"], ["delivered", "broken", "incorrect"]))->count() == 3;
            })
            ->count() == $product_dispatches->count();

        if (!$is_data_valid) {
            $response = $this->response("04", "invalid data send", [
                "products" => [
                    "status penerimaan tidak sesuai, masing-masing produk harus tiga data dengan status delivered, broken, incorrect",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        $is_data_valid = collect($request->products)
            ->groupBy(function ($product) use (&$group_by) {
                if (isset($product["product_id"]) && !empty($product["product_id"])) {
                    return $product["product_id"];
                }

                return $product["promotion_good_id"];
            })
            ->filter(function ($product_group, $product_id) use ($product_dispatches, $group_by) {
                return $product_group->sum("quantity") == $product_dispatches->filter(fn($product) => $product->$group_by == $product_id)->first()?->sent_unit_quantity
                && $product_group->sum("quantity_package") == $product_dispatches->filter(fn($product) => $product->$group_by == $product_id)->first()?->sent_package_quantity;
            })
            ->count() == $product_dispatches->count();

        if (!$is_data_valid) {
            $response = $this->response("04", "invalid data send", [
                "products" => [
                    "quantity tidak sesuai dispatch",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    public function afterReceive($receiving_good)
    {
        $this->receiving_goog_received->firstOrCreate([
            "delivery_order_id" => $receiving_good->delivery_order_id,
            "receiving_good_id" => $receiving_good->id,
        ]);

        NotificationReceivingGoodJob::dispatch($receiving_good);
        DipatchOnReceivedEvent::dispatch($receiving_good);

        $receiving_good
            ->receivingGoodDetail
            ->filter(fn($product) => $product->status == "delivered")
            ->each(function ($product) {
                DeliveryStatusCheckInReceivingGoodEvent::dispatch($product);
            });
    }
}
