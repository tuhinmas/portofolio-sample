<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Http\Requests\DriverRequest;
use Modules\DataAcuan\Transformers\DriverCollectionResource;
use Modules\DataAcuan\Transformers\DriverResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Concerns\DisablePagination;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request as RequestsRequest;

class DriverController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = Driver::class;
    protected $request = DriverRequest::class;
    protected $resource = DriverResource::class;
    protected $collectionResource = DriverCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "personel.contact",
        ];
    }

    public function includes(): array
    {
        return [];
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
            'transportation_type',
            "police_number",
            "id_driver",
            "driver_phone_number",
            "personel.name",
            'capacity',
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
            'transportation_type',
            "police_number",
            "id_driver",
            "driver_phone_number",
            "personel.name",
            'capacity',
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
            'transportation_type',
            "police_number",
            "id_driver",
            "personel.name",
            "driver_phone_number",
            'capacity',
            'created_at',
            'updated_at',
        ];
    }

     /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(RequestsRequest $request, array $requestedRelations): Builder
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
    public function runIndexFetchQuery(RequestsRequest $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("disabled_pagination")) {
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function armadaCapacityDetail(Request $request, Driver $driver)
    {
        $validation = Validator::make($request->all(), [
            "armada_id" => "required",
            // "date_delivery" => "required"
        ]);

        if ($validation->fails()) {
            return $this->response("04", "invalid data send", $validation->errors());
        }

        if (!$request->has('date_delivery')) {
            $request->merge([
                "date_delivery" => now()->format("Y-m-d")
            ]);
        }

        try {
            $capacity = $driver->findOrFail($request->armada_id);
            $capacity = $driver::query()
                ->with([
                    "dispatchOrder" => function ($QQQ) {
                        return $QQQ
                            // ->whereDoesntHave("deliveryOrder")
                            ->with([
                                "dispatchOrderDetail",
                                "deliveryOrder"
                            ]);
                    },
                    "personel.contact"
                ])
                ->where("id", $request->armada_id)
                ->first();

            $capacity_detail = [];
            $armada_capacity_used = 0;

            if ($capacity->dispatchOrder) {
                // return collect($capacity->dispatchOrder)->toArray();

                foreach (collect($capacity->dispatchOrder)->where('date_delivery', $request->date_delivery) as $dispatchOrder) {
                    $armada_capacity_used += collect($dispatchOrder->dispatchOrderDetail)->sum("package_weight");
                }
            }

            $capacity->capacity_available = $capacity->capacity - $armada_capacity_used;
            $capacity->unsetRelation("dispatchOrder");
            return $this->response("00", "success", $capacity);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th->getMessage(), 500);
        }
    }
}
