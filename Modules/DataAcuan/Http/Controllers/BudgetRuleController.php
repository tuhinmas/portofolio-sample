<?php

namespace Modules\DataAcuan\Http\Controllers;

use Carbon\Carbon;

use App\Traits\ResponseHandler;
use Modules\DataAcuan\Entities\BudgetRule;
use Modules\DataAcuan\Entities\Ppn;
use Modules\DataAcuan\Http\Requests\BudgetRequest;
use Modules\DataAcuan\Http\Requests\BudgetRuleRequest;
use Orion\Http\Controllers\Controller;
use Modules\DataAcuan\Http\Requests\PpnRequest;
use Modules\DataAcuan\Transformers\BudgetResource;
use Modules\DataAcuan\Transformers\BudgetRuleCollectionResource;
use Modules\DataAcuan\Transformers\PpnResource;
use Modules\DataAcuan\Transformers\PpnCollectionResource;
use Orion\Concerns\DisableAuthorization;

class BudgetRuleController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = BudgetRule::class;
    protected $request = BudgetRuleRequest::class;
    protected $resource = BudgetResource::class;
    protected $collectionResource = BudgetRuleCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
           "event",
           "budgetArea",
           "budget"
        ];
    }


    public function includes(): array
    {
        return [
        ];
    }

    /**
    * The attributes that are used for filtering.
    *
    * @return array
    */
    public function filterableBy() : array
    {
        return [
            'id', 
            'budget.name',
            'budgetArea.name',
            'type_budget', 
            "max_budget",
            "id_event",
            'id_budget_area', 
            'id_budget',
            'created_at', 
            'updated_at'
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
            'type_budget', 
            "max_budget",
            "id_event",
            'id_budget_area', 
            'id_budget',
            'created_at', 
            'updated_at'
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
            'type_budget', 
            "max_budget",
            "id_event",
            'id_budget_area', 
            'id_budget',
            'created_at', 
            'updated_at'
        ];
    }

    public function exposedScopes(): array
    {
        return [
            "search",
        ];
    }



}
