<?php

namespace Modules\DataAcuan\Http\Controllers;

use Orion\Http\Requests\Request;
use App\Traits\ResponseHandlerV2;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Entities\DealerGradeSuggestion;
use Modules\DataAcuan\Http\Requests\DealerGradeSuggestionRequest;
use Modules\DataAcuan\Transformers\DealerGradeSuggestionResource;
use Modules\DataAcuan\Transformers\DealerGradeSuggestionCollectionResource;
use Modules\DataAcuan\Jobs\DealerGradeSuggestion\SyncAllDealerGradeSuggestionJob;
use Modules\DataAcuan\Actions\DealerGradeSuggestion\GetDealerGradeByAttribueAction;

class DealerGradeSuggestionController extends Controller
{
    use ResponseHandlerV2;
    use DisableAuthorization;

    protected $model = DealerGradeSuggestion::class;
    protected $request = DealerGradeSuggestionRequest::class;
    protected $resource = DealerGradeSuggestionResource::class;
    protected $collectionResource = DealerGradeSuggestionCollectionResource::class;

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

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [
            "grading",
            "suggestedGrading"
        ];
    }

    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterAbleBy(): array
    {
        return column_lists(new DealerGradeSuggestion);
    }

    public function sortAbleBy(): array
    {
        return collect(column_lists(new DealerGradeSuggestion))->push("grading.name", "suggestedGrading.name")->toArray();
    }

    public function aggregates(): array
    {
        return [
        ];
    }

    public function beforeStore(Request $request, $model)
    {
        Grading::findOrFail($request->grading_id);
        $dealer_grade_suggestion = new GetDealerGradeByAttribueAction();

        if ($dealer_grade_suggestion($request->all())) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "tidak bisa membuat saran grade dealer, saran grade dealer dengan nilai ini sudah ada"
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill(
            collect($attributes)
                ->map(function ($value, $attribute) {
                    if (!$value) {
                        $value = 0;
                    }
                    return $value;
                })
                ->toArray()
        );

        /* infinite settle days set to null value */
        $entity->maximum_settle_days = $entity->is_infinite_settle_days ? null: $entity->maximum_settle_days;
        $entity->save();
    }

    public function afterStore(Request $request, $entity)
    {
        SyncAllDealerGradeSuggestionJob::dispatch();
    }

    public function beforeUpdate(Request $request, $model)
    {
        if ($request->has("grading_id")) {
            Grading::findOrFail($request->grading_id);
        }

        if ($request->has("payment_method_id")) {
            PaymentMethod::findOrFail($request->payment_method_id);
        }

        $dealer_grade_suggestion = new GetDealerGradeByAttribueAction();

        if ($dealer_grade_suggestion(collect($model)->merge($request->all())->toArray(), $model->id)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "tidak bisa membuat saran grade dealer, saran grade dealer dengan nilai ini sudah ada"
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        if ($request->has("proforma_last_minimum_amount") && $request->has("proforma_total_amount")) {
            if (!$request->proforma_last_minimum_amount && !$request->proforma_total_amount) {
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "tidak bisa meng-update saran grade, nilai proforma tidak boleh 0 semua"
                    ],
                ], 422);

                throw new HttpResponseException($response);
            }
        }
    }

    protected function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill(
            collect($attributes)
                ->map(function ($value, $attribute) {
                    if (!$value) {
                        $value = 0;
                    }
                    return $value;
                })
                ->toArray()
        );
        
        $entity->maximum_settle_days = $entity->is_infinite_settle_days ? null: $entity->maximum_settle_days;
        $entity->save();
    }

    public function afterUpdate(Request $request, $entity)
    {
        SyncAllDealerGradeSuggestionJob::dispatch();
    }

    public function beforeDestroy(Request $request, $model)
    {
        $response = $this->response("04", "invalid data send", [
            "message" => [
                "tidak boleh menghapus data saran grade, hubungi support"
            ],
        ], 422);
        throw new HttpResponseException($response);
    }
}
