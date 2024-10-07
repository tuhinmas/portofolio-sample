<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Modules\DataAcuan\Http\Requests\DealerPaymentMethodRequest;
use Modules\DataAcuan\Transformers\DealerPaymentMethodResource;
use Modules\DataAcuan\Transformers\DealerPaymentMethodCollectionResource;

class DealerPaymentMethodController extends Controller
{
    use DisableAuthorization;

    protected $model = DealerPaymentMethod::class;
    protected $request = DealerPaymentMethodRequest::class;
    protected $resource = DealerPaymentMethodResource::class;
    protected $collectionResource = DealerPaymentMethodCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "dealer",
            "paymentMethod",
        ];
    }

    public function includes(): array
    {
        return [
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
            "dealer_id",
            "payment_method_id",
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
            "payment_method_id",
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
            "payment_method_id",
            "created_at",
            "updated_at",
        ];
    }
}
