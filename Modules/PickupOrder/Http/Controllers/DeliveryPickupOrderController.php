<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\PickupOrder\Constants\DeliveryPickupOrderStatus;
use Orion\Concerns\DisableAuthorization;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Http\Requests\DeliveryPickupOrderRequest;
use Modules\PickupOrder\Transformers\Resources\DeliveryPickupOrderResource;
use Modules\PickupOrder\Transformers\Collections\DeliveryPickupOrderCollectionResource;
use Modules\PickupOrder\Repositories\PickupOrderRepository;

class DeliveryPickupOrderController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = DeliveryPickupOrder::class; 
    protected $request = DeliveryPickupOrderRequest::class;
    protected $resource = DeliveryPickupOrderResource::class;
    protected $collectionResource = DeliveryPickupOrderCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "warehouse",
            "driver"
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
    public function filterableBy(): array
    {
        return [
            "id",
            "status",
            "created_at",
            "updated_at",
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
            "id",
            "status",
            "created_at",
            "updated_at",
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
            "id",
            "status",
            "created_at",
            "updated_at"
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
        return $query->get();
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
        $entity->save();
    }

    public function batchStore(Request $request)
    {
        try {
            $pickupOrderRepository = new PickupOrderRepository();
            $response = $pickupOrderRepository->batchStore($request->all());
            return $this->response("00", "Pickup Order Saved", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save", $th->getMessage(), 500);
        }
    }

    public function listDeliveryPickupOrder(Request $request)
    {
        try {
            $pickupOrderRepository = new PickupOrderRepository();
            $response = $pickupOrderRepository->listDeliveryPickupOrder($request->all());
            return $this->response("00", "Pickup Order Saved", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save", $th->getMessage(), 500);
        }
    }
}
