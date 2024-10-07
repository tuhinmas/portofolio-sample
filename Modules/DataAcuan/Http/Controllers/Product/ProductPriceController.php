<?php

namespace Modules\DataAcuan\Http\Controllers\product;

use App\Traits\OrionValidationBeforeSave;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Http\Requests\product\ProductPriceRequest;
use Modules\DataAcuan\Transformers\product\ProductPriceCollectionResource;
use Modules\DataAcuan\Transformers\product\ProductPriceResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class ProductPriceController extends Controller
{
    use OrionValidationBeforeSave;
    use DisableAuthorization;

    protected $model = Price::class;
    protected $request = ProductPriceRequest::class;
    protected $resource = ProductPriceResource::class;
    protected $collectionResource = ProductPriceCollectionResource::class;

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
        return [];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return column_lists(new Price);
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return column_lists(new Price);
    }

    public function beforeStore(Request $request, $model)
    {
        $this->relationshipAssociateCheckv2($request, new AgencyLevel, "agency_level_id");
        $this->relationshipAssociateCheckv2($request, new Product, "product_id");
    }

    public function afterStore(Request $request, $model)
    {
        Product::query()
            ->where("id", $model->product_id)
            ->update([
                "het" => $model->het,
            ]);
    }

}
