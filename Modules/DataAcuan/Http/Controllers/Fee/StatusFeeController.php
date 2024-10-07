<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\DataAcuan\Http\Requests\StatusFeeRequest;
use Modules\DataAcuan\Transformers\StatusFeeResource;
use Modules\DataAcuan\Transformers\StatusFeeCollectionResource;

class StatusFeeController extends Controller
{
    use DisableAuthorization;

    protected $model = StatusFee::class;
    protected $request = StatusFeeRequest::class;
    protected $resource = StatusFeeResource::class;
    protected $collectionResource = StatusFeeCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [

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
        return [
        ];
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
            "name",
            "percentage",
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
            "name",
            "percentage",
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
            "name",
            "percentage",
            "created_at",
            "updated_at",
        ];
    }
}
