<?php

namespace Modules\Personel\Http\Controllers\Fee;

use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\ClassHelper\FeeSharingOriginActiveMapper;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\SalesOrder;

class MarketingFeeTrackerController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        Personel $personel,
        SalesOrder $sales_order,
        FeeSharingSoOrigin $fee_sharing_origin,
    ) {
        $this->personel = $personel;
        $this->sales_order = $sales_order;
        $this->fee_sharing_origin = $fee_sharing_origin;
    }

    /**
     * fee tracker
     *
     * @param Request $request
     * request include personel_id
     * @return void
     */
    public function __invoke(Request $request, FeeSharingOriginActiveMapper $fee_active_filter)
    {
        ini_set('max_execution_time', '900');

        $request->validate([
            "personel_id" => [
                "required",
            ],
            "year" => [
                "required",
            ],
            "quarter" => [
                "required",
            ],
        ]);

        try {
            $personel = $this->personel->query()
                ->with([
                    "position",
                    "supervisor",
                ])
                ->findOrFail($request->personel_id);

            $sales_orders = $this->sales_order->query()
                ->with([
                    "invoice" => function ($QQQ) {
                        return $QQQ->with([
                            "payment",
                        ]);
                    },
                    "statusFee",
                ])
                ->consideredMarketingSalesByQuarter($request->personel_id, $request->year, $request->quarter)
                ->orderBy("order_number")
                ->get();

            /* position purchaser */
            $purchaser_position = DB::table('fee_positions')
                ->whereNull("deleted_at")
                ->where("fee_as_marketing", true)
                ->first();

            /* fee sharing */
            $fee_sharing_origins = $this->fee_sharing_origin->query()
                ->with([
                    "position",
                    "salesOrderOrigin" => function ($QQQ) {
                        return $QQQ->with([
                            "direct" => function ($QQQ) {
                                return $QQQ->with([
                                    "invoice",
                                ]);
                            },
                        ]);
                    },
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice" => function ($QQQ) {
                                return $QQQ->with([
                                    "payment",
                                ]);
                            },
                        ]);
                    },
                    "salesOrderDetail" => function ($QQQ) {
                        return $QQQ->with([
                            "product",
                        ]);
                    },
                ])
                ->whereHas("salesOrder")
                ->where("personel_id", $request->personel_id)
                ->whereYear("confirmed_at", $request->year)
                ->whereRaw("quarter(confirmed_at) = ?", $request->quarter)
                ->get()

            /* mapping data by origin */
                ->groupBy("sales_order_origin_id")
                ->map(function ($fee_sharing_per_order_origin, $sales_order_origin_id) use ($purchaser_position) {
                    if ($sales_order_origin_id) {
                        $is_handover_exist = $fee_sharing_per_order_origin->filter(fn($fee_sharing) => $fee_sharing->handover_status)->first();

                        /* fee sharing has origin */
                        if ($is_handover_exist) {
                            $fee_sharing_per_order_origin
                                ->filter(function ($fee_sharing) use ($purchaser_position) {
                                    return !$fee_sharing->handover_status && $fee_sharing->position_id == $purchaser_position->position_id;
                                })
                                ->map(function ($fee_sharing) {
                                    $fee_sharing->fee_shared = 0;
                                    return $fee_sharing;
                                });
                        }
                    }
                    return $fee_sharing_per_order_origin;
                })
                ->flatten()

            /* mapping data by order detail that has no origin */
                ->groupBy("sales_order_detail_id")
                ->map(function ($fee_sharing_per_order_detail, $sales_order_detail_id) use ($purchaser_position) {
                    if ($sales_order_detail_id) {
                        $is_handover_exist = $fee_sharing_per_order_detail
                            ->filter(fn($fee_sharing) => !$fee_sharing->sales_order_origin_id)
                            ->filter(fn($fee_sharing) => $fee_sharing->handover_status)->first();

                        /* fee sharing has origin */
                        if ($is_handover_exist) {
                            $fee_sharing_per_order_detail
                                ->filter(fn($fee_sharing) => !$fee_sharing->sales_order_origin_id)
                                ->filter(function ($fee_sharing) use ($purchaser_position) {
                                    return !$fee_sharing->handover_status && $fee_sharing->position_id == $purchaser_position->position_id;
                                })
                                ->map(function ($fee_sharing) {
                                    $fee_sharing->fee_shared = 0;
                                    return $fee_sharing;
                                });
                        }
                    }
                    return $fee_sharing_per_order_detail;
                })
                ->flatten();

            return $fee_active_filter($fee_sharing_origins)
                ->map(function ($fee) {
                    $fee["is_active"] = true;
                    return $fee;
                })
                ->map(function ($fee) {
                    $fee["order_number"] = $fee->salesOrder->order_number;
                    $fee["product_name"] = $fee->salesOrderDetail->product->name;
                    unset($fee->salesOrder);
                    return $fee;
                })
                ->sortBy("order_number")
                ->groupBy("position_id")
                ->map(function ($fee_per_position) {
                    return $fee_per_position;
                    return $fee_per_position->sum("fee_shared");
                })
                ->map(function ($fee_per_position) {
                    // return $fee_per_position;
                    return $fee_per_position
                        ->sortBy("fee_shared")
                        ->map(function ($fee) {
                            $fee->fee_shared = number_format($fee->fee_shared, 2);
                            return $fee;
                        })
                        ->map(function ($fee) {
                            return $fee->only(["order_number", "fee_shared", "product_name", "confirmed_at"]);
                        })
                        ->values();
                });

            /* order as supervisor */
            $nomor = 0;
            $sales_order_as_supervisor = $this->sales_order->query()
                ->with([
                    "invoice" => function ($QQQ) {
                        return $QQQ->with([
                            "payment",
                        ]);
                    },
                ])
                ->whereIn("id", $fee_sharing_origins->pluck("sales_order_id")->toArray())
                ->whereNotIn("id", $sales_orders->pluck("id")->toArray())
                ->get()
                ->sortBy("order_number")
                ->map(function ($order) use ($personel, &$nomor, $fee_sharing_origins, $request) {
                    $nomor++;
                    $order["nomor"] = $nomor;
                    $order["join_date_days"] = Carbon::parse($personel->join_date)->startOfDay()->diffInDays(confirmation_time($order), false);
                    $order["join_date"] = $personel->join_date;
                    $order["confirmed_time"] = confirmation_time($order)->format("Y-m-d");
                    $supervisor_as = $fee_sharing_origins->where("sales_order_id", $order->id)->where("personel_id", $request->personel_id)->first();
                    $order["supervisor_position"] = $supervisor_as->position?->name;
                    return $order;
                })
                ->groupBy("supervisor_position");
            // ->map(function ($order) {
            //     return $order->only([
            //         "nomor",
            //         "order_number",
            //         "join_date_days",
            //         "join_date",
            //         "confirmed_time",
            //     ]);
            // })
            // ->values();

            return $this->response("00", "success", [
                "marketing" => collect($personel)
                    ->only(["name", "position", "join_date", "status", "supervisor"])
                    ->map(function ($value, $key) {

                        if ($key == "position") {
                            $value = $value["name"];
                        }

                        if ($key == "supervisor") {
                            $value = $value["name"];
                        }

                        return $value;
                    }),
                "fee_sharing_origins" => $fee_per_position,
                "sales_order_as_marketing" => $sales_orders->map(function ($order) {
                    return collect($order)
                        ->only([
                            "id",
                            "date",
                            "type",
                            "model",
                            "status",
                            "invoice",
                            "is_office",
                            "status_fee",
                            "counter_id",
                            "receipt_id",
                            "personel_id",
                            "order_number",
                            "agency_level",
                            "follow_up_days",
                            "is_marketing_freeze",
                            "afftected_by_return",
                        ])
                        ->map(function ($value, $key) {
                            if ($key == "invoice") {
                                $value = collect($value)->only([
                                    "id",
                                    "invoice",
                                    "payment",
                                    "last_payment",
                                    "payment_time",
                                    "created_at",
                                ]);
                            }
                            return $value;
                        });
                }),
                "sales_order_as_supervisor" => $sales_order_as_supervisor,
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
