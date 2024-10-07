<?php

namespace Modules\Invoice\Http\Controllers;

use Carbon\Carbon;
use App\Traits\DistributorStock;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\KiosDealer\Entities\Dealer;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Entities\AdjustmentStock;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Invoice\Events\AdjusmentDeletedEvent;
use Modules\Invoice\Events\AdjusmentToOriginEvent;
use Modules\Invoice\Http\Requests\AdjustmentStockRequest;
use Modules\Invoice\Transformers\AdjustmentStockResource;
use Modules\Invoice\Transformers\AdjustmentStockCollectionResource;
use Modules\Invoice\Events\AdjusmentStockMatchToDistributorContractEvent;

class AdjustmentStockController extends Controller
{
    use DisableAuthorization;
    use DistributorStock;

    protected $model = AdjustmentStock::class;
    protected $request = AdjustmentStockRequest::class;
    protected $resource = AdjustmentStockResource::class;
    protected $collectionResource = AdjustmentStockCollectionResource::class;

    /**
     * scope
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
        ];
    }

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [
            "dealer",
            "product.package",
            "personel.position",
        ];
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
            "distributorContract",
            "activeDistributorContract",
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
            'product_id',
            'dealer_id',
            'product_id',
            'opname_date',
            'real_stock',
            'product.name',
            'dealer.name',
            'is_first_stock',
            'contract_id',
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
            'product_id',
            'real_stock',
            'current_stock',
            'created_at',
            'updated_at',
            'is_first_stock',
            'contract_id',
        ];
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit): LengthAwarePaginator
    {
        return $query->paginate($paginationLimit);
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

        /* first stock */
        if (array_key_exists("is_first_stock", $attributes)) {
            if ($attributes["is_first_stock"] == "1") {
                $attributes["product_in_warehouse"] = $attributes["real_stock"];
                $attributes["product_unreceived_by_distributor"] = 0;
                $attributes["product_undelivered_by_distributor"] = 0;
                $contract_active = $this->distributorActiveContract($attributes["dealer_id"]);
                if ($contract_active) {
                    $attributes["opname_date"] = $contract_active->contract_start;
                }
            } else {
                if (array_key_exists("previous_contract_return", $attributes)) {
                    $attributes["real_stock"] = $attributes["product_in_warehouse"] + $attributes["product_unreceived_by_distributor"] - $attributes["product_undelivered_by_distributor"] + $attributes["previous_contract_return"];
                }
                else {
                    $attributes["real_stock"] = $attributes["product_in_warehouse"] + $attributes["product_unreceived_by_distributor"] - $attributes["product_undelivered_by_distributor"];
                }
            }
        }

        $entity->fill($attributes);
        $entity->save();
    }

    public function beforeStore(Request $request, $model)
    {
        $dealer = Dealer::query()
            ->whereHas("ditributorContract", function ($QQQ) {
                return $QQQ
                    ->where("contract_start", "<=", Carbon::now()->format("Y-m-d"))
                    ->where("contract_end", ">=", Carbon::now()->format("Y-m-d"));
            })
            ->findOrFail($request->dealer_id);

        Product::findOrFail($request->product_id);
    }

    public function afterStore(Request $request, $model)
    {

        $stock = $this->distributorProductStockPreviousBeforeAdjusment($model->dealer_id, $model->product_id, $model);
        if ($stock) {

            /* update current stok distributor ( not real stok) */
            $model->previous_stock = $stock->previous_stock;
            $model->current_stock = $stock->previous_stock;
        }

        $model->save();

        /**
         * contain 2 listener
         *
         * 1. if there sales order origin >= opname_date, add stock opname to origin
         *
         *
         * 2. compare previous stock before adjustment and current stock after adjusment
         * if stock after adjusment < previous stock, there must
         * self sales from retail. update it in origin
         */
        $adj_to_origin = AdjusmentToOriginEvent::dispatch($model);
        
        /**
         * adjustment match to distributor contract
         * set contract_id with contract was match
         */
        $adjustment_match_contract = AdjusmentStockMatchToDistributorContractEvent::dispatch($model);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $attributes["personel_id"] = auth()->user()->personel_id;

        /* first stock */
        if (array_key_exists("is_first_stock", $attributes)) {
            if ($attributes["is_first_stock"] == "1") {
                $attributes["product_in_warehouse"] = $attributes["real_stock"];
                $attributes["product_unreceived_by_distributor"] = 0;
                $attributes["product_undelivered_by_distributor"] = 0;
                $contract_active = $this->distributorActiveContract($attributes["dealer_id"]);
                if ($contract_active) {
                    $attributes["opname_date"] = $contract_active->contract_start;
                }
            } else {
                if (array_key_exists("previous_contract_return", $attributes)) {
                    $attributes["real_stock"] = $attributes["product_in_warehouse"] + $attributes["product_unreceived_by_distributor"] - $attributes["product_undelivered_by_distributor"] + $attributes["previous_contract_return"];
                }
                else {
                    $attributes["real_stock"] = $attributes["product_in_warehouse"] + $attributes["product_unreceived_by_distributor"] - $attributes["product_undelivered_by_distributor"];
                }
            }
        }

        $entity->fill($attributes);
        $entity->save();
    }

    protected function performDestroy(Model $entity): void
    {
        $entity->delete();
    }

    public function afterDestroy(Request $request, $model)
    {
        $event_delete = AdjusmentDeletedEvent::dispatch($model);
        $model->deleted_by = auth()->user()->personel_id;
        $model->save();
    }
}
