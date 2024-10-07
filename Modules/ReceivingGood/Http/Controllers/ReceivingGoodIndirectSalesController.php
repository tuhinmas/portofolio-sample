<?php

namespace Modules\ReceivingGood\Http\Controllers;

use Carbon\Carbon;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;
use Modules\ReceivingGood\Http\Requests\ReceivingGoodIndirectSalesRequest;
use Modules\ReceivingGood\Transformers\ReceivingGoodIndirectSalesResource;
use Modules\ReceivingGood\Transformers\ReceivingGoodIndirectSalesCollectionResource;

class ReceivingGoodIndirectSalesController extends Controller
{

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     * 
     */
    use ResponseHandler;
    use DisableAuthorization;
    use MarketingArea;

    protected $model = ReceivingGoodIndirectSale::class;
    protected $request = ReceivingGoodIndirectSalesRequest::class;
    protected $resource = ReceivingGoodIndirectSalesResource::class;
    protected $collectionResource = ReceivingGoodIndirectSalesCollectionResource::class;


    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    public function includes(): array
    {
        return [
            "receivingGoodDetailIndirect",
            "receivingGoodIndirectFile"
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
            'sales_order_id',
            'delivery_number',
            'status',
            'note',
            'date_received',
            'created_at',
            'updated_at',
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
            'sales_order_id',
            'delivery_number',
            'status',
            'note',
            'date_received',
            'created_at',
            'updated_at',
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
            'sales_order_id',
            'delivery_number',
            'status',
            'note',
            'date_received',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [

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
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function pointMarketingWithPersonel(HttpRequest $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        try {
            $pointMarketing = PointMarketing::whereHas('personel', function ($q) use ($request) {
                return $q->when($request->has("region_id"), function ($QQQ) use ($request) {
                    $marketing_list = $this->marketingListByAreaId($request->region_id);
                    return $QQQ->whereIn("id", $marketing_list);
                })
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request) {
                    $marketing_list = $this->marketingListByAreaId($request->sub_region_id);
                    return $QQQ->whereIn("id", $marketing_list);
                });
            })
            ->when($request->has('redeemable'), function ($QQQ) use ($request) {
                return $QQQ->whereIn("status", $request->redeemable);
            })
            ->when($request->has("personel_marketing_name"), function ($QQQ) use ($request) {
                return $QQQ->whereHas("personel", function ($QQQ) use ($request) {
                        return $QQQ->where("name", "like", "%" . $request->personel_marketing_name . "%");
                });
            })
            ->when($request->has("year"), function ($QQQ) use ($request) {
                return $QQQ->where("year", $request->year);
            })
            ->with("personel.position", "pointRedeem.prize")
            ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                $sort_type = "asc";
                if ($request->has("order_type")) {
                    $sort_type = $request->order_type;
                }
                if ($request->sorting_column == 'personel_marketing_name') {
                    return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'point_marketings.personel_id'), $sort_type);
                } else {
                    return $QQQ->orderBy($request->sorting_column, $sort_type);
                }
            });

            // Setup necessary information for LengthAwarePaginator
            if ($request->has("disabled_pagination")) {
                $datacollect = $pointMarketing->get();
            } else {
                $datacollect = $pointMarketing->paginate($request->limit > 0 ? $request->limit : 15);
            }

            return $this->response("00", "success get marketing poins", $datacollect);
        } catch (\Throwable $th) {
            return $this->response("01", "get Data marketing poins Failed ", $th->getMessage());
        }
    }
}
