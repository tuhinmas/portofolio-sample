<?php

namespace Modules\Invoice\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Invoice\Entities\EntrusmentPayment;
use Modules\Invoice\Http\Requests\EntrusmentPaymentRequest;
use Modules\Invoice\Transformers\EntrusmentPaymentResource;
use Modules\Invoice\Transformers\EntrusmentPaymentCollectionResource;

class EntrusmentPaymentController extends Controller
{
    use DisableAuthorization;

    protected $model = EntrusmentPayment::class;
    protected $request = EntrusmentPaymentRequest::class;
    protected $resource = EntrusmentPaymentResource::class;
    protected $collectionResource = EntrusmentPaymentCollectionResource::class;

    /**
     * scope
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
            "byDealer",
            "byProformaNumber",
            "unsettlePayment",
            "supervisor",
            "region",
            "whereMarketing",
            "personelBranch"
        ];
    }

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [
            "invoice",
            "invoice.payment",
            "personel",
            "personel.personel",
            "personel.personel.position",
            "paymentMethod",
        ];
    }

    /**
     * include data relation
     */
    public function includes(): array
    {
        return [
            "salesOrder.dealer",
            "salesOrder.dealer.agencyLevel",
            "salesOrder.subDealer",
            "salesOrder.agencyLevel",
            "invoice.salesOrder.dealer",
            "invoice.salesOrder.subDealer"
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
            'nominal',
            'invoice_id',
            'user_id',
            'payment_method_id',
            'date',
            'created_at',
            'updated_at',
            'invoice.invoice'
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
            'invoice.proforma_number',
            'payment_method_id',
            'invoice.invoice',
            'personel.name',
            'invoice_id',
            'created_at',
            'updated_at',
            'nominal',
            'user_id',
            'date',
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
        return $query->whereHas("invoice");
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
}
