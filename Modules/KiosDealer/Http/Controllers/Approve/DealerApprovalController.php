<?php

namespace Modules\KiosDealer\Http\Controllers\Approve;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Actions\Approval\DealerApprovalAction;

class DealerApprovalController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected Dealer $dealer,
        protected DealerTemp $dealer_temp,
    ) {}

    public function __invoke(DealerApprovalAction $approval_action, Request $request, $dealer_temp_id)
    {
        try {
            DB::beginTransaction();
            $dealer_temp = $this->dealer_temp->query()
                ->with([
                    "storeFix",
                    "dealerFix",
                    "dealerFile",
                    "subDealerFix",
                    "addressDetail",
                    "dealerChangeHistory"
                ])
                ->lockForUpdate()
                ->findOrFail($dealer_temp_id);

            if ($dealer_temp->status != "wait approval") {
                return $this->response("04", "invalid data send", [
                    "message" => [
                        "dealer submission status must 'wait approval'",
                    ],
                ], 422);
            }

            $dealer = $approval_action($dealer_temp);
            DB::commit();
            return $this->response("00", "success", $dealer);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed", $th);
        }
    }
}
