<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MandatoryProductAchievementV2Controller extends Controller
{
    use ResponseHandlerV2;

    public function __invoke(Request $request)
    {
        $request->merge([
            "year" => $request->year ?? now()->year,
        ]);

        $considere_indirect_column = considere_indirect_column();
        $considere_direct_column = considere_direct_column();

        try {
            /**
             * get sub region of marketing first separately to reduce
             * joining table, and cached, it rarely change so
             * we can save it in cache
             */
            $sub_region = collect();
            switch (true) {
                case Cache::has('sub_region_marketing'):
                    $sub_region = Cache::get('sub_region_marketing');
                    break;

                default:
                    $sub_region = DB::table('marketing_area_districts as md')
                        ->join("marketing_area_sub_regions as ms", "md.sub_region_id", "ms.id")
                        ->whereNull("md.deleted_at")
                        ->whereNull("ms.deleted_at")
                        ->select("md.sub_region_id", "ms.name as sub_region", "ms.region_id", DB::raw("if(md.personel_id <> ms.personel_id, md.personel_id, ms.personel_id) as marketing_id"))
                        ->groupBy(DB::raw("if(md.personel_id <> ms.personel_id, md.personel_id, ms.personel_id)"), "md.sub_region_id")
                        ->get();

                    Cache::put('sub_region_marketing', $sub_region, now()->addMinutes(120));
                    break;
            }

            /**
             * product mandatory data reference, also we will separated it, it rarely
             * change in whole year, and we not should grab it every page
             */
            $product_mandatory = DB::table('product_mandatory_pivots as pmp')
                ->join("products as pro", "pro.id", "pmp.product_id")
                ->join("product_mandatories as pm", "pm.id", "pmp.product_mandatory_id")
                ->join("product_groups as pg", "pg.id", "pm.product_group_id")
                ->whereNull("pm.deleted_at")
                ->select("pm.period_date", "pro.metric_unit", "pm.product_group_id", "pm.target as target_marketing", "pg.name as mandatory_product", "pro.id as product_id")
                ->get()
                ->groupBy([
                    fn($product) => $product->period_date,
                    fn($product) => $product->product_group_id,
                ])
                ->map(function ($group_per_period, $period_date) {
                    return $group_per_period->map(function ($group_per_group) use ($period_date) {
                        $detail = [
                            "period" => $period_date,
                            "volume" => 0,
                            "metric_unit" => $group_per_group->first()->metric_unit,
                            "product_group_id" => $group_per_group->first()->product_group_id,
                            "target_marketing" => $group_per_group->first()->target_marketing,
                            "mandatory_product" => $group_per_group->first()->mandatory_product,
                            "persentage_marketing" => 0,
                            "product_ids" => $group_per_group->pluck("product_id"),
                        ];

                        return $detail;
                    })->values();
                })
                ->values()
                ->collapse()
                ->filter(fn($product) => $product["period"] == $request->year)
                ->filter(function ($product) use ($request) {
                    if ($request->has("product_group_id") && !empty($request->product_group_id)) {
                        return in_array($product["product_group_id"], $request->product_group_id);
                    }
                    return $product;
                })
                ->values();

            /**
             * get all markertig with all status
             */
            $personels = DB::table('personels as p')
                ->join("positions as po", function ($join) {
                    $join
                        ->on("po.id", "p.position_id")
                        ->whereIn("po.name", marketing_positions());
                })

            /* filter marketing according ctive status histories */
                ->where(function ($QQQ) use ($request) {
                    return $QQQ

                    /* marketing has active or freeze status history in selected year */
                        ->whereExists(function ($QQQ) use ($request) {
                            $QQQ->select(DB::raw(1))
                                ->from('personel_status_histories as psh')
                                ->whereColumn('psh.personel_id', 'p.id')
                                ->where(function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->whereYear("psh.start_date", $request->year)
                                        ->orWhere(function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->whereYear("psh.end_date", $request->year)
                                                ->orWhereNull("psh.end_date");
                                        });
                                })
                                ->whereIn("psh.status", [1, 2]);
                        })

                        /* or marketing doesn't have active status history at all*/
                        ->orWhereNotExists(function ($QQQ) {
                            $QQQ->select(DB::raw(1))
                                ->from('personel_status_histories as psh')
                                ->whereColumn('psh.personel_id', 'p.id');
                        });
                })
                ->whereNull("p.deleted_at")
                ->select("p.id as marketing_id", "p.name as marketing", "po.name as position")

            /* filters */
                ->when(true, function ($QQQ) use ($request, $sub_region) {
                    return self::filter($QQQ, $request, $sub_region);
                })

            /* sort */
                ->when(true, function ($QQQ) use ($request, $sub_region) {
                    $QQQ = self::sort($QQQ, $request, $sub_region);
                })
                ->paginate($request->limit ?? 10);

            /**
             * get sales of the year, we will separate it to reduce join table and
             * and this data mostly change unlike personel data, so we should
             * separate this
             */
            $sales_orders = DB::table('sales_order_details as sod')
                ->join("products as p", "p.id", "sod.product_id")
                ->join("sales_orders as s", "s.id", "sod.sales_order_id")
                ->leftJoin("invoices as i", function ($join) {
                    $join
                        ->on("i.sales_order_id", "s.id")
                        ->where("s.type", "1")
                        ->whereNull("i.deleted_at");
                })

            /* filter year */
                ->where(function ($QQQ) use ($request, $considere_indirect_column, $considere_direct_column) {
                    return $QQQ
                        ->where(function ($QQQ) use ($request, $considere_indirect_column, $considere_direct_column) {
                            return $QQQ
                                ->where("s.type", "2")
                                ->whereYear("s.$considere_indirect_column", $request->year);
                        })
                        ->orWhereYear("i.$considere_direct_column", $request->year);
                })
                ->whereIn("sod.product_id", $product_mandatory->pluck("product_ids")->flatten()->toArray())
                ->whereIn("s.personel_id", $personels->pluck("marketing_id")->toArray())
                ->whereIn("s.status", considered_orders())
                ->whereNull("s.deleted_at")
                ->whereNull("sod.deleted_at")
                ->select("sod.product_id", "sod.quantity", "sod.returned_quantity", "p.volume", "s.personel_id")
                ->get();

            foreach ($personels as $personel) {
                $sub_region_marketing = $sub_region->filter(fn($sub_region) => $sub_region->marketing_id == $personel->marketing_id)->pluck("sub_region")->unique()->toArray();
                $personel->sales_year = $request->year;
                $personel->group_rmc = (!empty($sub_region_marketing) ? implode(", ", $sub_region_marketing) : null);
                $personel->data = $product_mandatory
                    ->map(function ($achievement_group) use ($sales_orders, $personel) {
                        $achievement = $sales_orders
                            ->filter(fn($order_detail) => $order_detail->personel_id == $personel->marketing_id)
                            ->filter(fn($order_detail) => in_array($order_detail->product_id, $achievement_group["product_ids"]->toArray()))
                            ->reduce(fn($metric, $order_detail) => $metric + (($order_detail->quantity - $order_detail->returned_quantity) * $order_detail->volume));

                        $achievement_group["volume"] = $achievement ?? 0;
                        $achievement_group["persentage_marketing"] = $achievement > 0 ? round(($achievement / $achievement_group["target_marketing"] * 100), 2) : 0;
                        unset($achievement_group["product_ids"]);
                        return $achievement_group;
                    });
            }

            return $this->response("00", "success", $personels);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }

    /**
     * filter marketing
     *
     * @param [type] $query
     * @param [type] $request
     * @param [type] $sub_regions
     * @return void
     */
    public function filter($query, $request, $sub_regions)
    {
        return $query
            ->when($request->has("marketing_id"), function ($QQQ) use ($request, $sub_regions) {
                return $QQQ->whereIn("p.id", $request->marketing_id);
            })
            ->when($request->has("region_id"), function ($QQQ) use ($request, $sub_regions) {
                $marketing_region = $sub_regions->filter(fn($sub_region) => in_array($sub_region->region_id, $request->region_id))->pluck("marketing_id")->toArray();
                return $QQQ->whereIn("p.id", $marketing_region);
            })
            ->when($request->has("sub_region_id"), function ($QQQ) use ($request, $sub_regions) {
                $marketing_sub_region = $sub_regions->filter(fn($sub_region) => in_array($sub_region->sub_region_id, $request->sub_region_id))->pluck("marketing_id")->toArray();
                return $QQQ->whereIn("p.id", $marketing_sub_region);
            });
    }

    /**
     * sort data
     *
     * @param [type] $query
     * @param [type] $request
     * @param [type] $sub_regions
     * @return void
     */
    public function sort($query, $request, $sub_regions)
    {
        return $query
            ->when($request->has("sort") && isset($request->sort["field"]),
                function ($QQQ) use ($request) {
                    $direction = $request->sort["direction"] ?? "asc";
                    return $QQQ

                    /* sort by marketing name */
                        ->when($request->sort["field"] == "marketing", function ($QQQ) use ($direction) {
                            return $QQQ->orderBy("p.name", $direction);
                        })

                        /* osrt by sub region name */
                        ->when($request->sort["field"] == "sub_regions", function ($QQQ) use ($direction) {
                            $district = DB::table('marketing_area_districts as md')
                                ->join("marketing_area_sub_regions as ms", "md.sub_region_id", "ms.id")
                                ->whereNull("md.deleted_at")
                                ->whereNull("ms.deleted_at")
                                ->select("ms.name as sub_region", DB::raw("if(md.personel_id <> ms.personel_id, md.personel_id, ms.personel_id) as marketing_id"))
                                ->groupBy(DB::raw("if(md.personel_id <> ms.personel_id, md.personel_id, ms.personel_id)"));

                            return $QQQ
                                ->joinSub($district, "sub", function ($join) {
                                    $join->on("sub.marketing_id", "p.id");
                                })
                                ->orderBy("sub.sub_region", $direction);

                        });

                },

                /* default sort */
                function ($QQQ) {
                    return $QQQ->orderBy("p.name");
                });
    }
}
