<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Repositories\PickupOrderRepository;
use Modules\PickupOrder\Http\Requests\PickupOrderDetailRequest;
use Modules\PickupOrder\Transformers\Resources\PickupOrderDetailResource;
use Modules\PickupOrder\Transformers\Collections\PickupOrderDetailCollectionResource;

class PickupOrderDetailController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = PickupOrderDetail::class; 
    protected $request = PickupOrderDetailRequest::class;
    protected $resource = PickupOrderDetailResource::class;
    protected $collectionResource = PickupOrderDetailCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return ["*"];
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
    public function filterableBy(): array
    {
        return column_lists(new $this->model);
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return column_lists(new $this->model);
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return column_lists(new $this->model);
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
            return $query->paginate($request->limit ? $request->limit : 15);
        }
    }

    public function actualCheckLoad(Request $request, $pickupOrderDetailId)
    {
        $request->validate([
            "actual_check_load" => [
                "required",
            ]
        ]);

        try {
            $findDispatch = PickupOrderDetail::find($pickupOrderDetailId);
            
            if (!$findDispatch) {
                return $this->response('04', 'invalid data send', "Pickup Detail Load id not found");
            }

            $response = [
                "match" => false,
                "quantity_actual_load" => $findDispatch->quantity_actual_load,
                "quantity_check_load" => $request->actual_check_load
            ];

            if ($findDispatch->quantity_actual_load ==  $request->actual_check_load) {
                $response = [
                    "match" => true,
                    "quantity_actual_load" => $findDispatch->quantity_actual_load,
                    "quantity_check_load" => $request->actual_check_load
                ];
            }

            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function storeActualCheck(Request $request, $pickupOrderId)
    {
        $rules = [
            'resource' => 'required|array|min:1',
            'resource.*.pickup_order_detail_id' => 'required|string',
            'resource.*.quantity_actual_checked' => 'required|numeric',
            'resource.*.checked' => 'required|boolean',
        ];
    
        $messages = [
            'resource.required' => 'The resource field is required.',
            'resource.array' => 'The resource field must be an array.',
            'resource.min' => 'The resource field must contain at least one item.',
            'resource.*.pickup_order_detail_id.required' => 'The pickup_order_detail_id field is required.',
            'resource.*.pickup_order_detail_id.string' => 'The pickup_order_detail_id field must be a string.',
            'resource.*.quantity_actual_checked.required' => 'The quantity_actual_checked field is required.',
            'resource.*.quantity_actual_checked.string' => 'The quantity_actual_checked field must be a numeric.',
            'resource.*.checked.required' => 'The checked field is required.',
            'resource.*.checked.boolean' => 'The checked field must be true or false.',
        ];
    
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            return $this->response('04', 'invalid data send', $validator->errors(), 422);
        }

        try {
            foreach ($request->resource as $key => $value) {
                $findDetail = PickupOrderDetail::find($value['pickup_order_detail_id']);
                if (!$findDetail) {
                    return $this->response('04', 'invalid data send', "Pickup Detail Load id not found");
                }

                if ($value['checked'] == true && $value['quantity_actual_checked'] != $findDetail->quantity_actual_load) {
                    return $this->response('04', 'invalid data send', [
                        "match" => false,
                        "id_load" => $findDetail->id,
                        "quantity_actual_load" => $findDetail->quantity_actual_load,
                        "quantity_actual_checked" => (int)$value['quantity_actual_checked']
                    ], 422);
                }
                
            }   

            foreach ($request->resource as $key => $value) {
                $findDetail = PickupOrderDetail::find($value['pickup_order_detail_id']);
                $findDetail->update([
                    'quantity_actual_checked' => $value['quantity_actual_checked'],
                    "is_checked" => $value['checked']
                ]);

                $response[] = $findDetail->fresh();
            }

            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

}
