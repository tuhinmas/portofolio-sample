<?php

namespace Modules\ReceivingGood\Http\Controllers;

use App\Traits\ResponseHandler;
use Ladumor\OneSignal\OneSignal;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\User;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\PickupOrder\Constants\PickupOrderStatus;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\ReceivingGood\Events\DipatchOnReceivedEvent;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\ReceivingGood\Entities\ReceivingGoodReceived;
use Modules\PickupOrder\Constants\DeliveryPickupOrderStatus;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Modules\ReceivingGood\Jobs\NotificationReceivingGoodJob;
use Modules\ReceivingGood\Http\Requests\ReceivingGoodRequest;
use Modules\ReceivingGood\Transformers\ReceivingGoodResource;
use Modules\ReceivingGood\Transformers\ReceivingGoodCollectionResource;

class ReceivingGoodController extends Controller
{
    use ResponseHandler;

    protected $model = ReceivingGood::class;
    protected $request = ReceivingGoodRequest::class;
    protected $resource = ReceivingGoodResource::class;
    protected $collectionResource = ReceivingGoodCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [
            "receivingGoodDetail",
        ];
    }

    public function includes(): array
    {
        return [
            "deliveryOrder",
            "invoiceHasOne",
            "receivingGoodFile",
            "invoiceHasOne.invoice",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterAbleBy(): array
    {
        return [
            'id',
            'created_at',
            "updated_at",
            'date_received',
            'delivery_status',
            'delivery_order_id',
            'invoiceHasOne.invoice.invoice',
            'deliveryOrder.delivery_order_number',
        ];
    }
    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function searchAbleBy(): array
    {
        return [
            'id',
            'created_at',
            "updated_at",
            'date_received',
            'delivery_order_id',
        ];
    }
    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function sortAbleBy(): array
    {
        return [
            'id',
            'created_at',
            "updated_at",
            'date_received',
            'delivery_order_id',
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->received_by = auth()->user()->personel_id;
        $entity->save();

        if ($entity->delivery_status == 2) {
            $deliveryOrder = DeliveryOrder::find($entity->delivery_order_id);

            $this->pushNotif([
                "subtitle" => 'Penerimaan Barang',
                "menu" => 'Penerimaan Barang',
                "id" => $deliveryOrder->id,
                "mobile_link" => null,
                "desktop_link" => null,
                "notification" => 'Surat Jalan No ' . $deliveryOrder->delivery_order_number . ' Sudah Diterima oleh ' . auth()->user()->personel->name,
            ]);

            ReceivingGoodReceived::firstOrCreate([
                "delivery_order_id" => $entity->delivery_order_id,
                "receiving_good_id" => $entity->id,
            ]);
        }
        NotificationReceivingGoodJob::dispatch($entity);
        DipatchOnReceivedEvent::dispatch($entity);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $receiving_good = ReceivingGood::query()
            ->where("delivery_order_id", $entity->delivery_order_id)
            ->where("delivery_status", "2")
            ->first();

        if (!$receiving_good) {
            $entity->save();
        }

        if ($entity->delivery_status == 2) {
            $deliveryOrder = DeliveryOrder::find($entity->delivery_order_id);
            $this->pushNotif([
                "subtitle" => 'Penerimaan Barang',
                "menu" => 'Penerimaan Barang',
                "id" => $deliveryOrder->id,
                "mobile_link" => null,
                "desktop_link" => null,
                "notification" => 'Surat Jalan No ' . $deliveryOrder->delivery_order_number . ' Sudah Diterima oleh ' . auth()->user()->personel->name,
            ]);

            ReceivingGoodReceived::firstOrCreate([
                "delivery_order_id" => $entity->delivery_order_id,
                "receiving_good_id" => $entity->id,
            ]);

            $deliveryOrder = DeliveryOrder::where('id', $entity->delivery_order_id)->first();
            if ($entity->is_promotion == true || $entity->dispatch_promotion_id != null) {
                DispatchPromotion::where("id", $deliveryOrder->dispatch_promotion_id)->update([
                    "status" => "received"
                ]);
            } else {
                DispatchOrder::where("id", $deliveryOrder->dispatch_order_id)->update([
                    "status" => "received"
                ]);
            }
        }
    }

    public function afterUpdate(Request $request, $model)
    {
        DipatchOnReceivedEvent::dispatch($model);
    }

    public function afterDestroy(Request $request, $model)
    {
        DipatchOnReceivedEvent::dispatch($model);
        ReceivingGoodReceived::query()
            ->where("receiving_good_id", $model->id)
            ->delete();
    }

    public function recevingGoodDetailOnReceiving(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "receiving_good_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        try {
            $receiving_good = ReceivingGood::query()
                ->with([
                    "receivingGoodDetail" => function ($QQQ) {
                        return $QQQ
                            ->with([
                                "product" => function ($QQQ) {
                                    return $QQQ->withTrashed();
                                },
                            ]);
                    },
                    "receivedBy.position",
                    "receivingGoodFile",
                    "deliveryOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "dealer" => function ($QQQ) {
                                return $QQQ->with([
                                    "adressDetail",
                                ]);
                            },
                            "marketing.position",
                            "dispatchOrder" => function ($QQQ) {
                                return $QQQ->with([
                                    "dispatchOrderDetailExcludeProduct" => function ($QQQ) {
                                        return $QQQ
                                            ->with([
                                                "product" => function ($QQQ) {
                                                    return $QQQ->withTrashed();
                                                },
                                            ]);
                                    },
                                    "invoice" => function ($QQQ) {
                                        return $QQQ->with([
                                            "invoiceProforma",
                                            "salesOrderOnly" => function ($QQQ) {
                                                return $QQQ
                                                    ->with([
                                                        "salesOrderDetail" => function ($QQQ) {
                                                            return $QQQ
                                                                ->with([
                                                                    "package",
                                                                    "product" => function ($QQQ) {
                                                                        return $QQQ->withTrashed();
                                                                    },
                                                                ]);
                                                        },
                                                    ]);
                                            },
                                            "deliveryOrder" => function ($QQQ) {
                                                return $QQQ
                                                    ->with([
                                                        "receivingGoodDetailHasReceived",
                                                    ]);
                                            },
                                        ]);
                                    },
                                ]);
                            },
                        ]);
                    },
                ])
                ->findOrFail($request->receiving_good_id);

            $receiving_good_detail = collect([]);
            $sort_type = "asc";
            if ($request->has("direction")) {
                $sort_type = $request->direction;
            }
            if ($receiving_good) {

                $product_order = collect($receiving_good->deliveryOrder->dispatchOrder->invoice->salesOrderOnly->salesOrderDetail)
                    ->map(function ($order_detail, $key) use ($receiving_good, &$product_received, &$product_package_received) {
                        $product_received = 0;
                        $product_package_received = 0;

                        $order_detail->quantity_package_order = $order_detail->package ? ($order_detail->quantity > 0 ? $order_detail->quantity / $order_detail->package->quantity_per_package : null) : null;
                        $order_detail->package_on_purchase = $order_detail->package;

                        collect($receiving_good->deliveryOrder->dispatchOrder->invoice->deliveryOrder)->each(function ($delivery_order) use ($order_detail, &$product_received, &$product_package_received, &$xx) {
                            $product_received += collect($delivery_order->receivingGoodDetailHasReceived)->where("product_id", $order_detail->product_id)->where("status", "delivered")->sum("quantity");
                            $product_package_received += collect($delivery_order->receivingGoodDetailHasReceived)->where("product_id", $order_detail->product_id)->where("status", "delivered")->sum("quantity_package");
                        });

                        $order_detail->quantity_received = $product_received;
                        $order_detail->quantity_unit_received = $product_received;
                        $order_detail->quantity_package_received = $product_package_received;

                        return $order_detail;
                    });
                    //->sortBy("product.name")->values();
                if ($request->sorting_column == 'product_name_order') {
                    $product_order=$product_order->sortBy("product.name")->values();
                    if ($sort_type != "asc") {
                        $product_order=$product_order->sortByDesc("product.name")->values();
                    }
                    
                } else {
                    // dd("aa");
                    $product_order=$product_order->sortBy("product.name")->values();
                }



                /**
                 * detail order
                 */
                $receiving_good_detail["detail_order"] = [
                    "proforma_number" => $receiving_good->deliveryOrder->dispatchOrder->invoice->invoice,
                    "invoice_number" => $receiving_good->deliveryOrder->dispatchOrder->invoice->invoiceProforma ? $receiving_good->deliveryOrder->dispatchOrder->invoice->invoiceProforma->invoice_proforma_number : null,
                    "marketing" => $receiving_good->deliveryOrder->marketing,
                    "marketing_position" => $receiving_good->deliveryOrder->marketing->position,
                    "dealer" => $receiving_good->deliveryOrder->dealer,
                    "delivery_location" => $receiving_good->deliveryOrder->dispatchOrder->invoice->salesOrderOnly->delivery_location,
                    "products" => $product_order,
                ];

                /**
                 * detail receiving
                 */
                $product_received = $receiving_good->receivingGoodDetail;
                $product_receiving = collect($receiving_good->deliveryOrder->dispatchOrder->dispatchOrderDetailExcludeProduct)
                    ->map(function ($dispatchOrderDetail, $key) use ($product_received, $receiving_good_detail) {
                        $package_on_purchase = collect($receiving_good_detail["detail_order"]["products"])->where("product_id", $dispatchOrderDetail->id_product)->first();
                        $quantity_unit_received = collect($product_received)->where("product_id", $dispatchOrderDetail->id_product)->where("status", "delivered")->sum("quantity");
                        $quantity_package_received = collect($product_received)->where("product_id", $dispatchOrderDetail->id_product)->where("status", "delivered")->sum("quantity_package");

                        $dispatchOrderDetail->quantity_unit_send = $dispatchOrderDetail->quantity_unit;
                        $dispatchOrderDetail->quantity_package_send = $dispatchOrderDetail->quantity_packet_to_send;
                        $dispatchOrderDetail->quantity_unit_received = $quantity_unit_received;
                        $dispatchOrderDetail->quantity_package_received = $quantity_package_received ? $quantity_package_received : 0;
                        $dispatchOrderDetail->package_on_purchase = $package_on_purchase ? $package_on_purchase->package_on_purchase : "-";

                        return $dispatchOrderDetail;
                    });

                if ($request->sorting_column == 'product_name_order_receiving') {
                    $product_receiving=$product_receiving->sortBy("product.name")->values();
                    if ($sort_type != "asc") {
                        $product_receiving=$product_receiving->sortByDesc("product.name")->values();
                    }
                } else {
                    $product_receiving=$product_receiving->sortBy('product.name')->values();
                }


                $receiving_good_detail["detail_receiving"] = [
                    "delivery_order_number" => $receiving_good->deliveryOrder->delivery_order_number,
                    "delivery_order_date_issued" => $receiving_good->deliveryOrder->date_delivery,
                    "receiving_good_date_received" => $receiving_good->date_received,
                    "received_by" => $receiving_good->receivedBy,
                    "products" => $product_receiving,
                    "attachment" => collect($receiving_good->receivingGoodFile)->where("attachment_status", "confirm")->values(),
                ];

                /**
                 * Broken product report
                 */
                $broken_product = collect($receiving_good->receivingGoodDetail)
                    ->map(function ($product, $key) use ($receiving_good_detail) {
                        $package_on_purchase = collect($receiving_good_detail["detail_order"]["products"])->where("product_id", $product->product_id)->first();
                        $product->package_on_purchase = $package_on_purchase ? $package_on_purchase->package_on_purchase : "-";
                        return $product;
                    })
                    ->where("status", "!=", "delivered");
                if ($request->sorting_column == 'product_name_broken') {
                    // dd($sort_type);
                    $broken_product = $broken_product->sortBy("product.name")->values();
                    if ($sort_type != "asc") {
                        $broken_product=$broken_product->sortByDesc("product.name")->values();
                    }
                } else {
                    $broken_product=$broken_product->sortBy('product.name')->values();
                }

                $receiving_good_detail["detail_broken_products"] = [
                    "products" => $broken_product,
                    "attachment" => collect($receiving_good->receivingGoodFile)->where("attachment_status", "!=", "confirm")->values(),
                ];
            }

            return $this->response("00", "success", $receiving_good_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get receiving good detail", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]);
        }
    }

    private function pushNotif($data)
    {
        $userDevices = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Riwayat Penerimaan Barang',
                    '(F) Detail Penerimaan Barang',
                ]);
            })
            ->get()->map(function ($q) {
                return $q->userDevices->map(function ($q) {
                    return $q->os_player_id;
                })->toArray();
            })->flatten()->toArray();

        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $data['subtitle'],
                "menu" => "data['menu']",
                "data_id" => $data['id'],
                "mobile_link" => $data['mobile_link'],
                "desktop_link" => $data['desktop_link'],
                "notification" => $data['notification'],
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $data['notification'],
                "in" => $data['notification'],
            ],
            "recipients" => 1,
        ];

        return OneSignal::sendPush($fields, $data['notification']);
    }
}
