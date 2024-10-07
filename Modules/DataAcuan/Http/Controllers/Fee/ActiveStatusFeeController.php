<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\DataAcuan\Entities\StatusFeeHistory;
use Modules\DataAcuan\Transformers\Fee\ActiveStatusFeeResource;

class ActiveStatusFeeController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected StatusFee $status_fee,
        protected StatusFeeHistory $status_fee_history,
    ) {}

    public function __invoke()
    {
        try {
            $active_status_fee = $this->status_fee_history->query()
                ->whereDate("date_start", "<=", now()->format("Y-m-d"))
                ->orderBy("date_start", "desc")
                ->first();

            if (!$active_status_fee) {
                $active_status_fee = $this->status_fee->get();
            }

            return new ActiveStatusFeeResource($active_status_fee);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
