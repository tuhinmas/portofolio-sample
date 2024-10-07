<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use Carbon\Carbon;
use Orion\Http\Requests\Request;
use App\Traits\ResponseHandlerV2;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\FeeFollowUpHistory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Http\Requests\FeeFollowUpHistoryRequest;
use Modules\DataAcuan\Transformers\Fee\FeeFollowUpHistoryResource;
use Modules\DataAcuan\Transformers\Fee\FeeFollowUpHistoryCollectionResource;

class FeeFolowUpHistoryController extends Controller
{
    use DisableAuthorization;
    use ResponseHandlerV2;

    protected $model = FeeFollowUpHistory::class;
    protected $request = FeeFollowUpHistoryRequest::class;
    protected $resource = FeeFollowUpHistoryResource::class;
    protected $collectionResource = FeeFollowUpHistoryCollectionResource::class;

    public function includes(): array
    {
        return ["*"];
    }

    public function sortableBy(): array
    {
        return column_lists(new $this->model);
    }

    public function beforeUpdate(Request $request, $model)
    {
        if (Carbon::parse($model->date_start)->format("Y-m-d") <= now()->format("Y-m-d")) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can not update history, date start is less or equal current date",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    public function beforeDestroy(Request $request, $model)
    {
        if (Carbon::parse($model->date_start)->format("Y-m-d") <= now()->format("Y-m-d")) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can not delete history, date start is less or equal current date",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }
    }
}
