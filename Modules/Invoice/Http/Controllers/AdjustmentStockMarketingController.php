<?php

namespace Modules\Invoice\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Orion\Concerns\DisableAuthorization;
use Modules\Invoice\Entities\AdjustmentStockMarketing;
use Modules\Invoice\Http\Requests\AdjustmentStockMarketingRequest;
use Modules\Invoice\Transformers\AdjustmentStockMarketingResource;
use Modules\Invoice\Transformers\AdjustmentStockMarketingCollectionResource;

class AdjustmentStockMarketingController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = AdjustmentStockMarketing::class;
    protected $request = AdjustmentStockMarketingRequest::class;
    protected $resource = AdjustmentStockMarketingResource::class;
    protected $collectionResource = AdjustmentStockMarketingCollectionResource::class;

    /**
     * scope
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [];
    }

    /**
     * include data relation
     */
    public function includes(): array
    {
        return [
            "dealer",
            "product.package",
            "personel.position",
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
            'dealer_id',
            'product_id',
            'opname_date',
            'real_stock',
            'product.name',
            'dealer.name',
            "contract_id"
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'opname_date',
            'real_stock',
            'current_stock',
            "contract_id",
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @param Request $request
     * @param Post $post
     */
    protected function beforeDestroy(Request $request, Model $entity)
    {
        // jika supervisor maka check hari yang bisa didelete hanya -1 hari dan sekarang
        if ($request->as_supervisor && $request->as_supervisor == "true") {
            $yesterday = Carbon::now()->subDay()->format("Y-m-d");
           
            $diffday = $entity->created_at->startOfDay()->diffInDays($yesterday);
            if (Carbon::parse($entity->created_at)->format("Y-m-d") < $yesterday) {
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "can not delete this data, stock created before yesterday can not delete",
                    ],
                ], 422);
                throw new HttpResponseException($response);
            }
        }
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $attributes["personel_id"] = auth()->user()->personel_id;
        $attributes["real_stock"] = $attributes["product_in_warehouse"] + $attributes["product_unreceived_by_distributor"] - $attributes["product_undelivered_by_distributor"] + ($attributes["previous_contract_return"] ?? 0);

        $entity->fill($attributes);
        $entity->save();
    }
}
