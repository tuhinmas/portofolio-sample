<?php

namespace Modules\DataAcuan\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\MaxDaysReference;
use Modules\DataAcuan\Entities\LogMaxDaysReference;
use Modules\DataAcuan\Http\Requests\MaxDaysReferenceRequest;
use Modules\DataAcuan\Transformers\MaxDaysReferenceResource;
use Modules\DataAcuan\Transformers\MaxDaysReferenceCollectionResource;

class MaxDaysReferenceController extends Controller
{
    use DisableAuthorization;

    protected $model = MaxDaysReference::class;
    protected $request = MaxDaysReferenceRequest::class;
    protected $resource = MaxDaysReferenceResource::class;
    protected $collectionResource = MaxDaysReferenceCollectionResource::class;

    public function includes(): array
    {
        return [
            "lastLogIndirect",
            "lastLogIndirect.personel",
            "lastLogIndirect.personel.position",
            "lastLogAgenda",
            "lastLogAgenda.personel",
            "lastLogAgenda.personel.position",
            "log",
            "log.personel.position",
        ];
    }

    public function filterableBy(): array
    {
        return [
            "maximum_days_for",
            "maximum_days",
            "description",
            "created_at",
            "updated_at",
            "year",
        ];
    }

    public function sortableBy(): array
    {
        return [
            "maximum_days_for",
            "maximum_days",
            "description",
            "created_at",
            "updated_at",
            "year",
        ];
    }

    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->forceFill($attributes);
        $entity->save();
    }

    public function afterStore(Request $request, $model)
    {
        if (auth()->user()->personel_id) {
            LogMaxDaysReference::create([
                "personel_id" => auth()->user()->personel_id,
                "max_days_reference_id" => $model->id,
            ]);
        }
    }

     /**
     * Builds Eloquent query for fetching entities in index method.
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
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $entity->forceFill($attributes);
        $entity->save();
    }

    public function afterUpdate(Request $request, $model)
    {
        if (auth()->user()->personel_id) {
            LogMaxDaysReference::create([
                "personel_id" => auth()->user()->personel_id,
                "max_days_reference_id" => $model->id,
            ]);
        }
    }
}
