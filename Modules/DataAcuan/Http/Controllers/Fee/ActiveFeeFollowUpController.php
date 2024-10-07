<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\FeeFollowUp;
use Modules\DataAcuan\Entities\FeeFollowUpHistory;
use Modules\DataAcuan\Transformers\Fee\ActiveFeeFollowUpResource;

class ActiveFeeFollowUpController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected FeeFollowUp $fee_follow_up,
        protected FeeFollowUpHistory $fee_follow_up_history,
    ) {}

    public function __invoke()
    {
        try {
            $active_fee_follow_up = $this->fee_follow_up_history->query()
                ->whereDate("date_start", "<=", now()->format("Y-m-d"))
                ->orderBy("date_start", "desc")
                ->first();

                if (!$active_fee_follow_up) {
                    $active_fee_follow_up = $this->fee_follow_up->get();
                }

            return new ActiveFeeFollowUpResource($active_fee_follow_up);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
