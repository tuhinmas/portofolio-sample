<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Modules\DataAcuan\Entities\Budget;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Http\Requests\BudgetRequest;
use Modules\DataAcuan\Transformers\BudgetResource;
use Modules\DataAcuan\Transformers\BudgetCollectionResource;

class BudgetController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = Budget::class;
    protected $request = BudgetRequest::class;
    protected $resource = BudgetResource::class;
    protected $collectionResource = BudgetCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    public function includes(): array
    {
        return [
            "BudgetRules"
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
            'name',
            'description',
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
            'name',
            'description',
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
            'name',
            'description',
            'created_at',
            'updated_at',
        ];
    }
}
