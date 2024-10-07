<?php

namespace Modules\KiosDealer\Http\Controllers;

use Carbon\Carbon;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;
use Orion\Concerns\DisableAuthorization;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\KiosDealer\Entities\DealerMinimalis;
use Modules\KiosDealer\Http\Requests\GradingDealerRequest;
use Modules\KiosDealer\Transformers\DealerGradingResource;
use Modules\KiosDealer\Transformers\DealerGradingCollectionResource;
// use Orion\Http\Requests\Request;

class DealerGradingController extends Controller
{
    use ResponseHandler;
    use MarketingArea;
    use DisableAuthorization;

    protected $model = DealerGrading::class;
    protected $request = GradingDealerRequest::class;
    protected $resource = DealerGradingResource::class;
    protected $collectionResource = DealerGradingCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "dealer",
            "grading",
            "salesOrder"
        ];
    }

    public function includes(): array
    {
        return [
            "dealer.personel",
            "personel",
            "personel.position",
            "personel.personel.position",
            "dealer.personel.position",
            "dealer.agencyLevel",
            "dealer.dealer_file",
            "dealer.dealerGrading",
            "dealer.dealerGradingBefore",
            "dealer.handover",
            "dealer.grading",
            "dealer.adress_detail",
            "dealer.salesOrder",
        ];
    }

    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterAbleBy(): array
    {
        return [
            "dealer.personel.name",
            "personel.name",
            "dealer_id",
            "dealer.dealerGradingBefore.grading_id",
            "grading_id",
            "custom_credit_limit",
            "user_id",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchAbleBy(): array
    {
        return [
            "dealer_id",
            "grading_id",
            "custom_credit_limit",
            "user_id",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortAbleBy(): array
    {
        return [
            "dealer_id",
            "grading_id",
            "custom_credit_limit",
            "user_id",
            "created_at",
            "updated_at",
        ];
    }

        /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
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
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("disabled_pagination")) {
            $data = $query
            ->orderBy("created_at", "desc")->get()->map(function ($item, $key) {
                $item["grading_before"] = DealerGrading::with("grading")->where("dealer_id", $item->dealer_id)->where("created_at", "<", $item->created_at)->orderBy("created_at", "desc")->first()?->grading;
                return $item;
            });
        } else {

            // sisa poin

            $data = $query
            ->paginate($request->limit ? $request->limit : 15)->through(function ($item, $key) {
                $item["grading_before"] = DealerGrading::with("grading")->where("dealer_id", $item->dealer_id)->where("created_at", "<", $item->created_at)->orderBy("created_at", "desc")->first()?->grading;
                return $item;
            });

            // dd($data);

            if ($request->sort_by == 'marketing_name') {
                $data = collect($query->get());
                if ($request->direction == "desc") {
                        // dd("asa");
                    $datacek = $data->sortByDesc(function ($item) {
                            return $item->dealer?->personel?->name;
                        })->values();
                } else {
                    $datacek = $data->sortBy(function ($item) {
                        return $item->dealer?->personel?->name;
                    })->values();
                }

                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $pageLimit = $request->limit > 0 ? $request->limit : 15;

                // slice the current page items
                $currentItems = $datacek->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

                // you may not need the $path here but might be helpful..
                $path = LengthAwarePaginator::resolveCurrentPath();

                // Build the new paginator
                $paginator = new LengthAwarePaginator($currentItems, count($datacek), $pageLimit, $currentPage, ['path' => $path]);

                return $paginator;
            }

        }
        return $data;
    }

    public function gradingGrafikFilter(Request $request)
    {

        try {

            if ($request->has('sub_region')) {
                unset($request->region);
            }

            $dealers = Dealer::query()->with('adress_detail')
                ->when($request->region, function ($QQQ)  use ($request) {

                    return $QQQ->whereHas('adress_detail', function ($query) use ($request) {
                        // $all_district = $this->districtListIdByAreaId($request->region);
                        $all_district = $this->districtListByAreaId($request->region);

                        return $query->whereIn('district_id', $all_district);
                    });
                })
                ->when($request->sub_region, function ($QQQ)  use ($request) {

                    return $QQQ->whereHas('adress_detail', function ($query) use ($request) {
                        // $all_district = $this->districtListIdByAreaId($request->region);
                        $all_district = $this->districtListByAreaId($request->sub_region);

                        return $query->whereIn('district_id', $all_district);
                    });
                })
                ->when($request->district_id, function ($QQQ)  use ($request) {

                    return $QQQ->whereHas('adress_detail', function ($query) use ($request) {
                        return $query->where('district_id', $request->district_id);
                    });
                })->whereHas('adress_detail', function ($query) {
                    return $query->where('type', 'dealer');
                })->get();

            // Hitung jumlah dealer berdasarkan grading
            $ranking = $dealers->groupBy('grading_id')->map(function ($dealers, $grading_id) {
                return [
                    'grading' => $dealers->first()->grading,
                    'count_dealer' => $dealers->count(),
                ];
            })->sortByDesc('count_dealer');

            return $this->response('00', 'success, get grading dealer', $ranking);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed, get grading dealer', $th->getMessage());
        }
    }

    public function gradingGrafikFilterActiveNotActive(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes

            $dealer_id = [];
            if ($request->sub_region) {
                $dealer_id = DB::table('dealers')->join('address_with_details', 'dealers.id', '=', 'address_with_details.parent_id')
                    ->select('dealers.id', 'address_with_details.district_id')->whereIn("address_with_details.district_id", $this->districtListByAreaId($request->sub_region))->whereNull("address_with_details.deleted_at")->get()->map(function ($data, $key) {
                        return $data->id;
                    });
                // return $dealer_id;
                unset($request->region);
            } else if ($request->region) {
                $dealer_id = DB::table('dealers')->join('address_with_details', 'dealers.id', '=', 'address_with_details.parent_id')
                    ->select('dealers.id', 'address_with_details.district_id')->whereIn("address_with_details.district_id", $this->districtListByAreaId($request->region))->whereNull("address_with_details.deleted_at")->get()->map(function ($data, $key) {
                        return $data->id;
                    });
            }

            $dealers =  DealerMinimalis::query()
                ->select("id", "grading_id", "deleted_at")
                ->whereHas("grading")
                ->when($request->sub_region || $request->region, function ($QQQ)  use ($request, $dealer_id) {
                    return $QQQ->whereIn('id', $dealer_id);
                })

                ->withCount(['salesOrder as order_count' => function ($query) {
                    return $query
                        ->where(function ($parameter) {
                            return $parameter
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) {
                                    return $QQQ->whereYear("created_at", Carbon::now());
                                });
                        })
                        ->orWhere(function ($parameter) {
                            return $parameter
                                ->where("type", "2")
                                ->whereYear("date", Carbon::now());
                        });
                }])
                ->withTrashed()
                ->get();

            $dealer_group_by_grading = collect($dealers)->sortBy("grading_id")->groupBy("grading_id");

            $detail = [
                "grading" => null,
                "count_dealer" => 0,
                "dealer_active" => 0,
                "dealer_not_active" => 0
            ];

            $ranking = [];

            DB::table('gradings')->whereNull("deleted_at")->get()->map(function ($data, $key) use ($detail, &$ranking) {
                $detail["grading"] = $data;
                $ranking[$data->id] = $detail;
            });

            $dealer_group_by_grading->map(function ($grading, $key) use (&$ranking) {

                $ranking[$key]["count_dealer"] = $grading->count();
                $ranking[$key]["dealer_active"] = collect($grading)->filter(function ($item) {
                    return is_null($item->deleted_at) && $item->order_count > 0;
                })->count();
                $ranking[$key]["dealer_not_active"] = collect($grading)->filter(function ($item) {
                    return !is_null($item->deleted_at) || $item->order_count == 0;
                })->count();
            });

            $ranking = collect($ranking)->sortByDesc("count_dealer");
            return $this->response('00', 'success, get grading dealer', $ranking);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed, get grading dealer', $th->getMessage());
        }
    }
}
