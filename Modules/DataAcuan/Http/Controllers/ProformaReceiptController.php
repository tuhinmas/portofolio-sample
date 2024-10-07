<?php

namespace Modules\DataAcuan\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Modules\DataAcuan\Http\Requests\ProformaReceiptRequest;
use Modules\DataAcuan\Transformers\ProformaReceiptResource;
use Modules\DataAcuan\Transformers\ProformaReceiptCollectionResource;

class ProformaReceiptController extends Controller
{
    use DisableAuthorization;
    use DisableAuthorization;

    protected $model = ProformaReceipt::class;
    protected $request = ProformaReceiptRequest::class;
    protected $resource = ProformaReceiptResource::class;
    protected $collectionResource = ProformaReceiptCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "confirmedBy.position",
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
    public function filterableBy(): array
    {
        return [
            "siup",
            "npwp",
            "tdp",
            "ho",
            "payment_info",
            "confirmed_by",
            "company_name",
            "company_address",
            "company_telephone",
            "company_hp",
            "company_email",
            "receipt_for",
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
            "siup",
            "npwp",
            "tdp",
            "ho",
            "payment_info",
            "confirmed_by",
            "company_name",
            "company_address",
            "company_telephone",
            "company_hp",
            "company_email",
            "receipt_for",
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
            "siup",
            "npwp",
            "tdp",
            "ho",
            "payment_info",
            "confirmed_by",
            "company_name",
            "company_address",
            "company_telephone",
            "company_hp",
            "company_email",
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
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
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return ProformaReceipt::findOrFail($key);
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $post
     * @param array $attributes
     */
    protected function performUpdate(Request $request, Model $receipt, array $attributes): void
    {
        $receipt->fill($attributes);
        $receipt->save();
    }

}
