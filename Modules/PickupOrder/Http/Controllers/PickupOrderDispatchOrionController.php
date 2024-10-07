<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Http\Requests\PickupOrderDispatchRequest;
use Modules\PickupOrder\Http\Requests\PickupOrderRequest;
use Modules\PickupOrder\Transformers\Collections\PickupOrderCollectionResource;
use Modules\PickupOrder\Transformers\Collections\PickupOrderDispatchCollectionResource;
use Modules\PickupOrder\Transformers\Resources\PickupOrderDispatchResource;
use Modules\PickupOrder\Transformers\Resources\PickupOrderResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class PickupOrderDispatchOrionController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = PickupOrderDispatch::class;
    protected $request = PickupOrderDispatchRequest::class;
    protected $resource = PickupOrderDispatchResource::class;
    protected $collectionResource = PickupOrderDispatchCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            // "dispatch.deliveryOrder",
            // "dispatch.invoice"
        ];
    }

    public function includes(): array
    {
        return [
            "dispatchOrder",
            "dispatchOrder.addressDelivery.district",
            "dispatchOrder.addressDelivery.city",
            "dispatchOrder.addressDelivery.province",
            "dispatchPromotion.addressDelivery.district",
            "dispatchPromotion.addressDelivery.city",
            "dispatchPromotion.addressDelivery.province",
            "dispatchOrder.invoice",
            "dispatchOrder.deliveryOrder",
            "dispatchOrder.invoice.salesOrder.dealer",
            "dispatchOrder.invoice.salesOrder.subDealer",
            "dispatchPromotion.promotionGoodRequest.createdBy",
            "dispatchPromotion.promotionGoodRequest.event.dealer",
            "dispatchPromotion.promotionGoodRequest.event.subDealer",
            "dispatchPromotion.promotionGoodRequest.event.personel",
            "dispatchPromotion.deliveryOrder",
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
            "pickup_order_id"
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
            "pickup_order_id"
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [];
    }

    public function aggregates(): array
    {
        return [];
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

}