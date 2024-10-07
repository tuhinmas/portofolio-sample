<?php

namespace Modules\KiosDealer\Http\Controllers\Inactive;

use App\Traits\MarketingArea;
use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Transformers\InactiveDealer\InactiveDealerResouceCollection;

class DealerInactiveController extends Controller
{
    use ResponseHandlerV2;
    use MarketingArea;

    public function __construct(
        protected DealerV2 $dealer,
    ) {

    }

    /**
     * Inactive dealer is dealer that has no order in period of time
     * in marketing will 45 days or base on data reference
     * on support will 60 days or base data reference
     *
     * @return void
     */
    public function __invoke(Request $request)
    {
        $request->merge([
            "order_type" => $request->order_type ?? "asc",
        ]);

        try {
            $personel_id = $request->personel_id ?? auth()->user()->personel_id;
            $inactive_days = DB::table('fee_follow_ups')
                ->whereNull("deleted_at")
                ->orderBy("follow_up_days")
                ->first();

            $days = (int) $inactive_days->follow_up_days;
            if (!auth()->user()->hasAnyRole(is_all_data())) {
                $days -= 15;
                $personels_id = $this->getChildren(auth()->user->personel_id);
            }

            $active_date = now()->subDays((int) $days)->startOfDay();

            /* last 45 days order or base data reference, to get active dealer*/
            $active_dealer = DB::table('sales_orders as s')
                ->whereNull("s.deleted_at")
                ->leftJoin('invoices as i', function ($join) use ($days) {
                    $join->on('s.id', '=', 'i.sales_order_id')
                        ->whereNull("i.deleted_at")
                        ->where("i.created_at", ">", now()->subDays((int) $days));
                })
                ->whereIn("s.status", considered_orders())
                ->whereRaw("IF(s.type = 2, s.date is not null, i.id is not null)")
                ->whereRaw("IF(s.type = 2, s.date > ?, i.created_at > ?)", [$active_date, $active_date])
                ->orderByRaw("IF(s.type = 2, s.date, i.created_at) desc")
                ->where("model", "1")
                ->select("s.store_id")
                ->get();

            /* new dealer during 45 days considered active */
            $active_new_dealer = DB::table('dealers as d')
                ->whereNull("d.deleted_at")
                ->whereNotIn("d.id", $active_dealer->pluck("store_id")->toArray())
                ->where("d.created_at", ">", now()->subDays($days))
                ->select("d.id", "d.created_at")
                ->orderByDesc("d.created_at")
                ->get();

            /* dealer who have purchases in last 60/45 days */
            $active_dealer = $active_dealer->pluck("store_id")->merge($active_new_dealer->pluck("id"))->unique()->toArray();

            $available_sort = [
                "name",
                "dealer_id",
                "marketing_name",
                "agency_level_name",
            ];

            $dealers = DB::table("dealers as d")
                ->whereNull("d.deleted_at")

            /* excluding active dealer */
                ->whereNotIn("d.id", $active_dealer)
                ->join('agency_levels as agl', function ($join) use ($days) {
                    $join
                        ->on("agl.id", "d.agency_level_id")
                        ->whereNull("agl.deleted_at");
                })

                ->leftJoin('personels as p', function ($join) use ($days) {
                    $join
                        ->on("p.id", "d.personel_id")
                        ->whereNull("p.deleted_at");
                })

                ->leftJoin('positions as po', function ($join) use ($days) {
                    $join
                        ->on("po.id", "p.position_id")
                        ->whereNull("po.deleted_at");
                })

            /* filter by name, dealer_id, owner, marketing */
                ->when($request->has('filter'), function ($QQQ) use ($request) {
                    return $QQQ
                        ->where(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where("d.name", "like", "%" . $request->filter . "%")
                                ->orWhere("d.dealer_id", "like", "%" . $request->filter . "%")
                                ->orWhere("d.owner", "like", "%" . $request->filter . "%")
                                ->orWhere("p.name", "like", "%" . $request->filter . "%");
                        });
                })

            /* filter by personel_id */
                ->when($request->has("personel_id"), function ($q) use ($personel_id) {
                    return $q->whereIn('personel_id', [$personel_id]);
                })

            /* filter by region */
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    return $QQQ
                        ->join('address_with_details as add', function ($join) use ($request) {
                            $join
                                ->on("add.parent_id", "d.id")
                                ->whereNull("add.deleted_at")
                                ->where("add.type", "dealer")
                                ->where("add.region_id", $request->region_id)
                                ->limit(1);
                        });
                })

            /* personel brach filter, e.g rico */
                ->when($request->personel_branch, function ($QQQ) use ($request) {
                    $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", $request->personel_branch)->pluck("region_id");
                    $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
                    return $QQQ->whereIn("personel_id", $marketing_on_branch);
                })

                ->select("d.*", "agl.name as agency_level_name", "p.name as marketing_name", "po.name as marketing_position_name")
                ->when($request->has("sorting_column"), function ($QQQ) use ($request, $available_sort) {
                    return match (true) {
                        in_array($request->sorting_column, $available_sort) => $QQQ->orderBy($request->sorting_column, $request->order_type),
                        default => $QQQ->orderBy("dealer_id", $request->order_type),
                    };
                })

                ->when(!$request->has("sorting_column"), function ($QQQ) use ($request) {
                    return $QQQ->orderBy("dealer_id", $request->order_type);
                })
                ->paginate($request->limit ? $request->limit : 10);

            return new InactiveDealerResouceCollection(collect($dealers));
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show dealers', $th);
        }
    }
}
