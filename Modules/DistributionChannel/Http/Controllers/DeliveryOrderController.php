<?php

namespace Modules\DistributionChannel\Http\Controllers;

use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use App\Traits\SupervisorCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\ReceiptDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use Ladumor\OneSignal\OneSignal;
use Modules\Organisation\Entities\Organisation;
use Orion\Http\Requests\Request as RequestOrion;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\PromotionGood\Entities\PromotionGoodDispatchOrder;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;
use Modules\DistributionChannel\Http\Requests\DeliveryOrderRequest;
use Modules\DistributionChannel\Jobs\DeliveryOrderNotificationJob;
use Modules\DistributionChannel\Notifications\DeliveryOrderNotification;
use Modules\DistributionChannel\Transformers\DeliveryOrderResource;
use Modules\DistributionChannel\Transformers\DeliveryOrderCollectionResource;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\ReceivingGood\Entities\ReceivingGood;

class DeliveryOrderController extends Controller
{
    use SupervisorCheck;
    use ResponseHandler;
    use MarketingArea;

    protected $model = DeliveryOrder::class;
    protected $request = DeliveryOrderRequest::class;
    protected $resource = DeliveryOrderResource::class;
    protected $collectionResource = DeliveryOrderCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "receiptDetail",
        ];
    }

    public function includes(): array
    {
        return [
            "dealer",
            "dealer.adressDetail.province",
            "receipt",
            "receivingGoods",
            "receivingGoods.receivedBy",
            "receivingGoods.receivedBy.position",
            "receipt.confirmedBy",
            "receipt.confirmedBy.position",

            "dispatchOrder",
            "promotionGoodDispatchOrder",
            "dispatchOrder.driver",
            "dispatchOrder.driver.personel",
            "dispatchOrder.driver.personel.contact",
            "dispatchOrder.promotionGoodRequest.event",
            "dispatchOrder.promotionGoodRequest.event.eventPreference",
            "dispatchOrder.promotionGoodRequest.createdBy",
            "dispatchOrder.promotionGoodRequest.createdBy.position",
            "dispatchOrder.promotionGoodRequest.confirmedBy",
            "dispatchOrder.promotionGoodRequest.confirmedBy.position",

            'dispatchOrder.addressDelivery',
            'dispatchOrder.addressDelivery.city',
            'dispatchOrder.addressDelivery.district',
            'dispatchOrder.addressDelivery.province',

            "dispatchOrder.invoice",
            "dispatchOrder.invoice.salesOrder",
            "dispatchOrder.invoice.salesOrder.personel",
            "dispatchOrder.invoice.salesOrder.personel.position",

            "dispatchOrder.invoice.salesOrder.dealer",
            "dispatchOrder.invoice.salesOrder.dealer.personel",
            "dispatchOrder.invoice.salesOrder.dealer.personel.position",

            "dispatchOrder.invoice.salesOrder.dealer.adress_detail",
            "dispatchOrder.invoice.salesOrder.dealer.adress_detail.city",
            "dispatchOrder.invoice.salesOrder.dealer.adress_detail.province",
            "dispatchOrder.invoice.salesOrder.dealer.adress_detail.district",

            "dispatchOrder.warehouse",
            "dispatchOrder.pickupOrder",
            "dispatchOrder.pickupOrder.armada",
            "dispatchOrder.pickupOrder.warehouse",
            "dispatchOrder.pickupOrder.armada.personel",

            "dispatchOrder.dispatchOrderFiles",
            "dispatchOrder.dispatchOrderDetail",
            "dispatchOrder.dispatchOrderDetail.salesOrderDetail",
            "dispatchOrder.dispatchOrderDetail.product.package",

            "dispatchPromotion",
            "dispatchPromotion.dispatchPromotionDetails",
            "dispatchPromotion.promotionGoodRequest",

            "dispatchPromotion.promotionGoodRequest.event",
            "dispatchPromotion.promotionGoodRequest.event.eventPreference",
            "dispatchPromotion.promotionGoodRequest.createdBy",
            "dispatchPromotion.promotionGoodRequest.createdBy.position",
            "dispatchPromotion.promotionGoodRequest.confirmedBy",
            "dispatchPromotion.promotionGoodRequest.confirmedBy.position",
            "dispatchPromotion.warehouse",
            "dispatchPromotion.driver.personel.contact",
            "dispatchPromotion",
            "dispatchPromotion.deliveryOrder",
            "dispatchPromotion.dispatchPromotionDetails",
            "dispatchPromotion.dispatchPromotionDetails.promotionGood",
            "dispatchPromotion.dispatchPromotionDetails.promotionGood.product",
            "dispatchPromotion.deliveryOrder.receivingGoods",
            "dispatchPromotion.deliveryOrder.receivingGoods.receivedBy",
            "dispatchPromotion.deliveryOrder.receivingGoods.receivedBy.position",
            "dispatchPromotion.deliveryOrder.receivingGoods.receivingGoodDetail",
            "dispatchPromotion.deliveryOrder.receivingGoods.receivingGoodFile",

            "dispatchPromotion.promotionGoodRequest.promotionGoodRequestAddresses",
            "dispatchPromotion.promotionGoodRequest.promotionGoodRequestAddressPromotion",
            "dispatchPromotion.promotionGoodRequest.promotionGoodRequestAddressReceivingGood",
            "dispatchPromotion.deliveryAddress",
            "dispatchPromotion.deliveryAddress.*",
            "dispatchOrder.dispatchOrderDetail.salesOrderDetail",
            "dispatch",

            "receivingGoodHasReceived"

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
            'byDealerNameDealerOwnerCustIdDistrictCityProvince',
            "statusAndReceiving",
            'hasReceivingGoods',
            'deliveryCanceled',
            'personelBranch',
            'byMarketingId',
            'byDealerName',
            'supervisor',
            'byPickupNumber',
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
            "date_delivery",
            "dispatch_order_id",
            "operational_manager_id",
            "marketing_id",
            "dealer_id",
            "organisation_id",
            "receipt_detail_id",
            "delivery_order_number",
            "status",
            "created_at",
            "updated_at",
            "dispatchOrder.dispatch_order_number",
            "dispatchOrder.invoice.invoice",
            "dispatch.dispatch_order_number",
            "dispatchOrder.pickupOrder.pickup_number"
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            'id',
            "date_delivery",
            "dispatch_order_id",
            "operational_manager_id",
            "marketing_id",
            "dealer_id",
            "organisation_id",
            "receipt_detail_id",
            "delivery_order_number",
            "status",
            "created_at",
            "updated_at",
            "dispatchOrder.dispatch_order_number",
            "dispatchOrder.invoice.invoice",
            "dispatch.dispatch_order_number",
            "dispatchOrder.pickupOrder.pickup_number"
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            "date_delivery",
            "receivingGoods.date_received",
            "dispatch_order_id",
            "operational_manager_id",
            "marketing_id",
            "dealer_id",
            "organisation_id",
            "receipt_detail_id",
            "delivery_order_number",
            "status",
            "created_at",
            "updated_at",
            "dispatchOrder.dispatch_order_number",
            "dispatchOrder.invoice.invoice",
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(RequestOrion $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(RequestOrion $request, Builder $query, int $paginationLimit)
    {
        if ($request->sort_by) {

            $sortedResult = $query
            // ->whereHas("invoice")
                ->get();

            if ($request->sort_by == 'invoice_number') {
                if ($request->direction == "desc") {
                    // dd("sas");
                    $sortedResult = $sortedResult->sortByDesc(function ($item) {
                        return $item->dispatchOrder?->invoice?->invoice;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $sortedResult->sortBy(function ($item) {
                        return $item->dispatchOrder?->invoice?->invoice;
                    })->values();
                }
            }

            if ($request->sort_by == 'dispatch_order_number') {
                if ($request->direction == "desc") {
                    $sortedResult = $sortedResult->sortByDesc(function ($item) {
                        return $item->dispatchOrder?->dispatch_order_number;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $sortedResult->sortBy(function ($item) {
                        return $item->dispatchOrder?->dispatch_order_number;
                    })->values();
                }
            }

            if ($request->sort_by == 'buyer') {
                if ($request->direction == "desc") {
                    // dd("sas");
                    $sortedResult = $sortedResult->sortByDesc(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->dealer->name;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $sortedResult->sortBy(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->dealer->name;
                    })->values();
                }
            }

            if ($request->sort_by == 'location_buyer') {
                if ($request->direction == "desc") {
                    // dd("sas");
                    $sortedResult = $sortedResult->sortByDesc(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->dealer->address;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $sortedResult->sortBy(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->dealer->address;
                    })->values();
                }
            }

            if ($request->sort_by == 'marketing_name') {
                if ($request->direction == "desc") {
                    // dd("sas");
                    $sortedResult = $sortedResult->sortByDesc(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->personel?->name;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $sortedResult->sortBy(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->personel?->name;
                    })->values();
                }
            }

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // slice the current page items
            $currentItems = $sortedResult->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $data = new LengthAwarePaginator($currentItems, count($sortedResult), $pageLimit, $currentPage, ['path' => $path]);
        } else {
            $data = $query
            // ->whereHas("invoice")
                ->paginate($request->limit > 0 ? $request->limit : 15);
        }

        return $data;
    }

    /**
     * perform update
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     * @return void
     */
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $month_conversion = [
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

        if ($request->has('dispatch_order_id') && !$request->is_promotion) {
            $dispatch = DispatchOrder::query()
                ->with("invoice.salesOrder")
                ->findOrFail($attributes["dispatch_order_id"]);

            $dealer = DB::table('dealers')->where("id", $dispatch->invoice->salesOrder->store_id)->first();
            $attributes["marketing_id"] = $dealer->personel_id;
            $attributes["dealer_id"] = $dealer->id;
        }

        $om = Personel::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->where("name", "Operational Manager");
            })
            ->first();

        $last_order_number = DB::table('delivery_orders')
            ->whereNull("deleted_at")
            ->whereYear("created_at", now())
            ->orderBy("order_number", "desc")
            ->lockForUpdate()
            ->first();

        if (!$last_order_number) {
            $order_number = (object) ["order_number" => 0];
            $last_order_number = $order_number;
        }

        /* get receipt template */
        $receipt = DB::table('proforma_receipts')->whereNull("deleted_at")->where("receipt_for", "4")->orderBy("created_at", "desc")->first();
        $attributes["receipt_id"] = $receipt ? $receipt->id : null;
        $attributes["confirmed_by"] = $om ? $om->id : null;
        $attributes["created_by"] = auth()->user() ? auth()->user()?->personel_id : null;

        $organisation = $this->createReceiptDetail();
        $attributes["organisation_id"] = $organisation ? $organisation["organisation"]->id : null;
        $attributes["operational_manager_id"] = $om ? $om->id : null;
        $attributes["receipt_detail_id"] = $organisation["receipt_detail"]->id;

        $attributes["order_number"] = $last_order_number->order_number + 1;
        if ($request->has('delivery_order_number')) {
            $attributes["delivery_order_number"] = $request->delivery_order_number;
        } else {
            $delevery_number = Carbon::now()->format("Y") . "/SJ-" . $month_conversion[Carbon::now()->format("m")] . "/" . str_pad($last_order_number->order_number + 1, 5, 0, STR_PAD_LEFT);
            $attributes["delivery_order_number"] = $delevery_number;
        }

        if ($request->has('is_promotion') && $request->is_promotion == "1") {
            // dd($request->is_promotion);
            if ($request->has('delivery_order_number')) {
                $attributes["delivery_order_number"] = $request->delivery_order_number;
            } else {
                $last_order_number = DB::table('delivery_orders')
                    ->whereNull("deleted_at")
                    ->whereYear("created_at", now())
                    ->orderBy("order_number_promotion", "desc")
                    ->first();

                if (!$last_order_number) {
                    $order_number = (object) ["order_number_promotion" => 0];
                    $last_order_number = $order_number;
                }

                $attributes["order_number_promotion"] = $last_order_number->order_number_promotion + 1;
                $delivery_number = Carbon::now()->format("Y") . "/SJL-" . $month_conversion[Carbon::now()->format("m")] . "/" . str_pad($last_order_number->order_number_promotion + 1, 5, 0, STR_PAD_LEFT);
                $attributes["delivery_order_number"] = $delivery_number;
            }
        }

        /**
         * header image default
         */
        if (!$request->has("image_header_link")) {
            $attributes["image_header_link"] = "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Shipping.png";
        }

        /**
         * footer image default
         */
        if (!$request->has("image_footer_link")) {
            $attributes["image_footer_link"] = "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Title.png";
        }

        $entity->fill($attributes);
        $entity->save();
    }

    public function afterStore(Request $request, $model)
    {
        if ($model->status == "send") {
            DeliveryOrderNumber::create([
                "dispatch_order_id" => $model->dispatch_order_id,
                "dispatch_promotion_id" => $model->dispatch_promotion_id,
                "delivery_order_id" => $model->id,
                "delivery_order_number" => $model->delivery_order_number,
            ]);
        }

        if ($model->is_promotion == 1) {
            $promotionGoodDispatch = PromotionGoodDispatchOrder::where('delivery_order_id', $model->id)->first();
            if ($promotionGoodDispatch) {
                DeliveryOrder::where('id', $model->id)->update([
                    'marketing_id' => optional($promotionGoodDispatch->promotionGoodRequest)->created_by,
                ]);
            }
        }
        /**
         * model SalesOrderHistoryChangeStatus is for log order status only
         */
        // SalesOrderHistoryChangeStatus::create([
        //     "sales_order_id" => $model->dispatchOrder ? $model->dispatchOrder->invoice->sales_order_id : $model->dispatchPromotion->invoice->sales_order_id,
        //     "type" => "1",
        //     "delivery_order_id" => $model->id,
        //     "status" => "created_delivery_order",
        //     "personel_id" => Auth::user()->personel_id,
        //     "note" => "Surat jalan diterbitkan pada ".$model->created_at." dengan nomor ".$model->delivery_order_number,
        // ]);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $attributes["updated_by"] = auth()->user() ? auth()->user()?->personel_id : null;
        $entity->fill($attributes);
        $entity->save();

        if (array_key_exists("date_delivery", $attributes) && $entity->is_promotion == 0) {
            $updatedDeliveryOrderDate = DispatchOrder::findOrFail($entity->dispatch_order_id);
            $updatedDeliveryOrderDate->date_delivery = $attributes['date_delivery'];
            $updatedDeliveryOrderDate->save();
        }

        if ($entity->is_promotion == 1) {
            DispatchPromotion::where('id', $entity->dispatch_promotion_id)->update([
                'date_delivery' => date('Y-m-d', strtotime($entity->date_delivery)),
            ]);
        }

        /**
         * model SalesOrderHistoryChangeStatus is for log order status only
         */
        // SalesOrderHistoryChangeStatus::create([
        //     "sales_order_id" => $entity->dispatchOrder ? $entity->dispatchOrder->invoice->sales_order_id : $entity->dispatchPromotion->invoice->sales_order_id,
        //     "type" => "1",
        //     "delivery_order_id" => $entity->id,
        //     "status" => "send_delivery_order",
        //     "personel_id" => Auth::user()->personel_id,
        //     "note" => "Surat jalan ".$entity->delivery_order_number." dikirimkan pada ".$entity->created_at,
        // ]);
    }

    /**
     * create struk
     *
     * @return void
     */
    public function createReceiptDetail()
    {
        $text_message = [
            "note_aggrement" => "Segala kerusakan dan kehilangan pada saat pengiriman menjadi tanggung jawab Armada / Supir setelah diterima dengan bukti tandatangan dibawah.
            Dengan ditandatanganinya surat jalan ini, maka surat jalan ini dipakai sekaligus sebagai tanda terima barang.",
            "note_checked" => "Barang - barang tersebut diatas sudah dicek, dihitung, dan telah diterima dengan kondisi yang baik dan benar oleh :",
        ];

        $organisation = Organisation::query()
            ->with("address")
            ->whereNull("deleted_at")
            ->where("name", "Javamas Agrophos")
            ->first();

        $organisation_address = collect($organisation->address)->where("type", "kantor")->first();
        if (!$organisation_address) {
            $organisation_address = "alamat organisasi kosong";
        } else {
            $organisation_address = $organisation_address->detail_address;
        }

        $receipt_detail = ReceiptDetail::create([
            "siup" => $organisation->siup,
            "npwp" => $organisation->npwp,
            "tdp" => $organisation->tdp,
            "ho" => $organisation->ho,
            "company_name" => $organisation->name,
            "company_address" => $organisation_address,
            "company_telephone" => $organisation->telephone,
            "company_hp" => $organisation->hp,
            "company_email" => $organisation->email,
            "note" => json_encode($text_message),
        ]);

        return [
            "organisation" => $organisation,
            "receipt_detail" => $receipt_detail,
        ];
    }

    public function deliveryTime(Request $request)
    {
        // tanggal penerimaan - tanggal kirim
        try {

            if ($request->year) {
                $year = $request->year;
            } else {
                $year = Carbon::now()->startOfYear()->format('Y');
            }

            if ($request->has("sub_region_id")) {
                unset($request->region_id);
            } elseif ($request->has("region_id")) {
                unset($request->sub_region_id);
            }

            $dispatch_order = DispatchOrder::query()
                ->with([
                    "deliveryOrder" => function ($QQQ) {
                        return $QQQ
                            ->with("receivingGoods")
                            ->whereHas("receivingGoods");
                    },
                    "invoice",
                ])
                ->whereHas('invoice')
                ->whereHas("deliveryOrder", function ($QQQ) {
                    return $QQQ->whereHas("receivingGoods");
                })
                ->when($request->region_id, function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas('deliveryOrder', function ($QQQ) use ($request) {
                            return $QQQ->whereHas("dealer.addressDetail", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereIn("district_id", $this->districtListByArea($request->region_id));
                            });
                        });
                })
                ->when($request->sub_region_id, function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas('deliveryOrder', function ($QQQ) use ($request) {
                            return $QQQ->whereHas("dealer.addressDetail", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereIn("district_id", $this->districtListByArea($request->sub_region_id));
                            });
                        });
                })
                ->whereNotNull('type_driver')
                ->whereYear("date_delivery", $year)
                ->get();

            // return $dispatch_order;
            // return $dispatch_order[0]->deliveryOrder->receivingGoods;
            $data_map = $dispatch_order->map(function ($data, $key) {
                // condition days
                $to = Carbon::parse($data->invoice->created_at)->format('Y-m-d');
                $from = Carbon::parse($data->deliveryOrder->receivingGoods->date_received)->format('Y-m-d');
                $days = Carbon::createFromFormat('Y-m-d', $to)->diffInDays(Carbon::createFromFormat("Y-m-d", $from), false);
                $data->days_delivery_times = $days;
                return $data;
            });

            // return $data_map;

            $detail = [
                "persentase_delivery_times" => null,
            ];

            $detail["persentase_delivery_times"]["zero_to_three"]["persentase"] = (collect($data_map)->where("days_delivery_times", ">=", 0)->where("days_delivery_times", "<", 3)->count() / (collect($data_map)->where("days_delivery_times", ">=", 0)->count() ?: 1)) * 100;
            $detail["persentase_delivery_times"]["zero_to_three"]["count"] = collect($data_map)->where("days_delivery_times", ">=", 0)->where("days_delivery_times", "<", 3)->count();

            $detail["persentase_delivery_times"]["three_to_five"]["persentase"] = (collect($data_map)->where("days_delivery_times", ">=", 3)->where("days_delivery_times", "<=", 5)->count() / (collect($data_map)->where("days_delivery_times", ">=", 0)->count() ?: 1)) * 100;
            $detail["persentase_delivery_times"]["three_to_five"]["count"] = collect($data_map)->where("days_delivery_times", ">=", 3)->where("days_delivery_times", "<=", 5)->count();

            $detail["persentase_delivery_times"]["more_five"]["persentase"] = (collect($data_map)->where("days_delivery_times", ">", 5)->count() / (collect($data_map)->where("days_delivery_times", ">=", 0)->count() ?: 1)) * 100;
            $detail["persentase_delivery_times"]["more_five"]["count"] = collect($data_map)->where("days_delivery_times", ">", 5)->count();

            $detail["total"] = collect($data_map)->where("days_delivery_times", ">=", 0)->count();

            return $this->response("00", "success to get data delivery times", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get data point Origin", $th->getMessage());
        }
    }

    public function deliveryTypeDriver(Request $request)
    {
        try {
            if ($request->year) {
                $year = $request->year;
            } else {
                $year = Carbon::now()->startOfYear()->format('Y');
            }

            if ($request->has("sub_region_id")) {
                unset($request->region_id);
            } elseif ($request->has("region_id")) {
                unset($request->sub_region_id);
            }

            $dispatch_order = DispatchOrder::query()
                ->with([
                    "deliveryOrder" => function ($QQQ) {
                        return $QQQ
                            ->with("receivingGoods")
                            ->whereHas("receivingGoods");
                    },
                    "invoice",
                ])
                ->whereHas('invoice')
                ->whereHas("deliveryOrder", function ($QQQ) {
                    return $QQQ->whereHas("receivingGoods");
                })
                ->when($request->region_id, function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas('deliveryOrder', function ($QQQ) use ($request) {
                            return $QQQ->whereHas("dealer.addressDetail", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereIn("district_id", $this->districtListByArea($request->region_id));
                            });
                        });
                })
                ->when($request->sub_region_id, function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas('deliveryOrder', function ($QQQ) use ($request) {
                            return $QQQ->whereHas("dealer.addressDetail", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereIn("district_id", $this->districtListByArea($request->sub_region_id));
                            });
                        });
                })
                ->whereNotNull('type_driver')
                ->whereYear("date_delivery", $year)
                ->get();

            $detail = [
                "persentase_driver_type" => null,
            ];

            $detail["persentase_driver_type"]["internal"]["persentase"] = (collect($dispatch_order)->where("type_driver", "internal")->count() / (collect($dispatch_order)->count() ?: 1)) * 100;
            $detail["persentase_driver_type"]["internal"]["count"] = collect($dispatch_order)->where("type_driver", "internal")->count();

            $detail["persentase_driver_type"]["external"]["persentase"] = (collect($dispatch_order)->where("type_driver", "external")->count() / (collect($dispatch_order)->count() ?: 1)) * 100;
            $detail["persentase_driver_type"]["external"]["count"] = collect($dispatch_order)->where("type_driver", "external")->count();

            $detail["persentase_driver_type"]["paket"]["persentase"] = (collect($dispatch_order)->where("type_driver", "paket")->count() / (collect($dispatch_order)->count() ?: 1)) * 100;
            $detail["persentase_driver_type"]["paket"]["count"] = collect($dispatch_order)->where("type_driver", "paket")->count();
            $detail["total"] = collect($dispatch_order)->count();

            return $this->response("00", "success to get data delivery times", $detail);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed', $th->getMessage());
        }
    }

    public function deliveryDriver(Request $request)
    {
        try {
            if ($request->year) {
                $year = $request->year;
            } else {
                $year = Carbon::now()->startOfYear()->format('Y');
            }

            if ($request->has("sub_region_id")) {
                unset($request->region_id);
            } elseif ($request->has("region_id")) {
                unset($request->sub_region_id);
            }
            ini_set('max_execution_time', 1500); //3 minutes
            $dispatch_order = DispatchOrder::query()
                ->with([
                    "deliveryOrder" => function ($QQQ) {
                        return $QQQ
                            ->with("receivingGoods")
                            ->whereHas("receivingGoods");
                    },
                    "invoice",
                ])
                ->whereHas("deliveryOrder", function ($QQQ) {
                    return $QQQ->whereHas("receivingGoods");
                })
                ->whereHas('invoice')
                ->when($request->region_id, function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas('deliveryOrder', function ($QQQ) use ($request) {
                            return $QQQ->whereHas("dealer.addressDetail", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereIn("district_id", $this->districtListByArea($request->region_id));
                            });
                        });
                })
                ->when($request->sub_region_id, function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas('deliveryOrder', function ($QQQ) use ($request) {
                            return $QQQ->whereHas("dealer.addressDetail", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereIn("district_id", $this->districtListByArea($request->sub_region_id));
                            });
                        });
                })
                ->whereNotNull('type_driver')
                ->whereYear("date_delivery", $year)
                ->get();
            // return $dispatch_order;
            $data_map = $dispatch_order->map(function ($data, $key) {
                // condition days
                $to = Carbon::parse($data->invoice->created_at)->format('Y-m-d');
                $from = Carbon::parse($data->deliveryOrder->receivingGoods->date_received)->format('Y-m-d');
                $days = Carbon::createFromFormat('Y-m-d', $to)->diffInDays(Carbon::createFromFormat("Y-m-d", $from), false);
                $data->days_delivery_times = $days;
                return $data;
            });

            $data_group_by = $data_map->groupBy([
                function ($val) {
                    return Carbon::parse($val->invoice->created_at)->translatedFormat('F');
                },
            ]);

            $detail = [
                // 'year' => Carbon::parse($start_date)->format("Y"),
                'Januari' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Februari' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Maret' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'April' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Mei' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Juni' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Juli' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Agustus' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'September' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Oktober' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'November' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
                'Desember' => [
                    [
                        "name" => '0-3 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '3-5 Hari',
                        "data" => [0, 0, 0],
                    ],
                    [
                        "name" => '5 Hari ke atas',
                        "data" => [0, 0, 0],
                    ],
                ],
            ];
            // $periodyear = CarbonPeriod::create($start_date, $end_date);
            // foreach ($periodyear as $years => $valuemonth) {
            //     $detail[$valuemonth->translatedFormat('F')] = 0;
            // }

            // return $data_group_by;
            foreach (collect($data_group_by) as $key => $data) {
                // zero to three , internal
                $detail[$key][0]["data"][0] = collect($data)->where("days_delivery_times", ">=", 0)->where("days_delivery_times", "<", 3)->where("type_driver", "internal")->count();
                // zero to three , external
                $detail[$key][0]["data"][1] = collect($data)->where("days_delivery_times", ">=", 0)->where("days_delivery_times", "<", 3)->where("type_driver", "external")->count();
                // zero to three , paket
                $detail[$key][0]["data"][2] = collect($data)->where("days_delivery_times", ">=", 0)->where("days_delivery_times", "<", 3)->where("type_driver", "paket")->count();

                // three to five days, internal
                $detail[$key][1]["data"][0] = collect($data)->where("days_delivery_times", ">=", 3)->where("days_delivery_times", "<=", 5)->where("type_driver", "internal")->count();
                // three to five days , external
                $detail[$key][1]["data"][1] = collect($data)->where("days_delivery_times", ">=", 3)->where("days_delivery_times", "<=", 5)->where("type_driver", "external")->count();
                // three to five days , paket
                $detail[$key][1]["data"][2] = collect($data)->where("days_delivery_times", ">=", 3)->where("days_delivery_times", "<=", 5)->where("type_driver", "paket")->count();

                // more five days, internal
                $detail[$key][2]["data"][0] = collect($data)->where("days_delivery_times", ">", 5)->where("type_driver", "internal")->count();
                // more five days , external
                $detail[$key][2]["data"][1] = collect($data)->where("days_delivery_times", ">", 5)->where("type_driver", "external")->count();
                // more five days , paket
                $detail[$key][2]["data"][2] = collect($data)->where("days_delivery_times", ">", 5)->where("type_driver", "paket")->count();
            }

            return $this->response("00", "success to get data delivery times", $detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get data", $th->getMessage());
        }
    }
}
