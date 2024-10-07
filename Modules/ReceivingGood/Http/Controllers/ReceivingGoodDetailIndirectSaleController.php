<?php

namespace Modules\ReceivingGood\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Modules\DataAcuan\Entities\Product;
use Modules\ReceivingGood\Entities\ReceivingGoodDetailIndirectSale;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;
use Modules\ReceivingGood\Http\Requests\ReceivingGoodDetailIndirectSaleRequest;
use Modules\ReceivingGood\Transformers\ReceivingGoodDetailIndirectSaleCollectionResource;
use Modules\ReceivingGood\Transformers\ReceivingGoodDetailIndirectSaleResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class ReceivingGoodDetailIndirectSaleController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = ReceivingGoodDetailIndirectSale::class;
    protected $request = ReceivingGoodDetailIndirectSaleRequest::class;
    protected $resource = ReceivingGoodDetailIndirectSaleResource::class;
    protected $collectionResource = ReceivingGoodDetailIndirectSaleCollectionResource::class;


    public function alwaysIncludes(): array
    {
        return [
            
        ];
    }

    public function includes(): array
    {
        return [
            "receivingGoodIndirect"
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
            'receiving_good_id',
            'status',
            'note',
            'quantity',
            'quantity_package',
            'product_id',
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
            'receiving_good_id',
            'status',
            'note',
            'quantity',
            'quantity_package',
            'product_id',
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
            'receiving_good_id',
            'status',
            'note',
            'quantity',
            'quantity_package',
            'product_id',
            'created_at',
            'updated_at',
        ];
    }



    public function beforeStore(Request $request, $model)
    {
        $rece = ReceivingGoodIndirectSale::findOrFail($request->receiving_good_id);
        $product = Product::query()
            ->whereHas("salesOrderDetail", function ($QQQ) use ($rece) {
                return $QQQ
                ->whereHas("sales_order", function ($QQQ) use ($rece) {
                    return $QQQ->where("id", $rece->sales_order_id);
                });
            })
            ->findOrFail($request->product_id);
    }
}
