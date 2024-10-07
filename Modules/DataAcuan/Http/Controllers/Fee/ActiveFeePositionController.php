<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Entities\FeePositionHistory;
use Modules\DataAcuan\Transformers\Fee\ActiveFeePositionResource;

class ActiveFeePositionController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected FeePositionHistory $fee_position_history,
        protected FeePosition $fee_position,
    ) {}

    public function __invoke()
    {
        try {
            $active_fee_position = $this->fee_position_history->query()
                ->whereDate("date_start", "<=", now()->format("Y-m-d"))
                ->orderBy("date_start", "desc")
                ->first();

            if (!$active_fee_position) {
                $active_fee_position = $this->fee_position->query()
                    ->with([
                        "position",
                        "feeCashMinimumOrder"
                    ])
                    ->get();
            }

            return new ActiveFeePositionResource($active_fee_position);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
