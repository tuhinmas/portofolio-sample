<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use Carbon\Carbon;
use Orion\Http\Requests\Request;
use App\Traits\ResponseHandlerV2;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\FeePositionHistory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Http\Requests\FeePositionHistoryRequest;
use Modules\DataAcuan\Transformers\Fee\FeePositionHistoryResource;
use Modules\DataAcuan\Transformers\Fee\FeePositionHistoryCollectionResource;

class FeePositionHistoryController extends Controller
{
    use DisableAuthorization;
    use ResponseHandlerV2;

    protected $model = FeePositionHistory::class;
    protected $request = FeePositionHistoryRequest::class;
    protected $resource = FeePositionHistoryResource::class;
    protected $collectionResource = FeePositionHistoryCollectionResource::class;

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