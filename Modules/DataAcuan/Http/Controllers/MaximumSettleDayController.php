<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Modules\DataAcuan\Entities\MaximumSettleDay;
use Modules\DataAcuan\Http\Requests\MaximumSettleDayRequest;
use Modules\DataAcuan\Transformers\MaximumSettleDayCollectionResource;
use Modules\DataAcuan\Transformers\MaximumSettleDayResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class MaximumSettleDayController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = MaximumSettleDay::class;
    protected $request = MaximumSettleDayRequest::class;
    protected $resource = MaximumSettleDayResource::class;
    protected $collectionResource = MaximumSettleDayCollectionResource::class;

    public function includes(): array
    {
        return [
            "personel",
            "personel.position",
        ];
    }
    
    public function filterableBy(): array
    {
        return [
            "personel_id",
            "max_settle_for",
            "days",
            "year",
            "created_at",
            "updated_at",
        ];
    }

    public function sortableBy(): array
    {
        return [
            "personel_id",
            "personel.name",
            "max_settle_for",
            "days",
            "year",
            "created_at",
            "updated_at",
        ];
    }

    public function syncMaxSettleDays(Request $request)
    {
        try {
            $model = MaximumSettleDay::updateOrCreate([
                "max_settle_for" => $request->max_settle_for,
                "year" => $request->year,
            ], [
                "personel_id" => $request->personel_id,
                "days" => $request->days,
            ]);

            return $this->response("01", "failed", $model);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }
}
