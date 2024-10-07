<?php

namespace Modules\KiosDealer\Http\Controllers\Approve;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Actions\Approval\SubDealerApprovalAction;

class SubDealerApprovalController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SubDealer $sub_dealer,
        protected SubDealerTemp $sub_dealer_temp,
    ) {}

    public function __invoke(SubDealerApprovalAction $approval_action, Request $request, $sub_dealer_temp_id)
    {
        try {
            DB::beginTransaction();
            $sub_dealer_temp = $this->sub_dealer_temp->query()
                ->with([
                    "storeFix",
                    "subDealerFile",
                    "subDealerFix",
                    "addressDetail",
                    "subDealerChangeHistory",
                ])
                ->lockForUpdate()
                ->findOrFail($sub_dealer_temp_id);

            if (!in_array($sub_dealer_temp->status, ['filed', 'submission of changes'])) {
                return $this->response("04", "invalid data send", [
                    "message" => [
                        "sub dealer submission status must 'filed' or 'submission of changes'",
                    ],
                ], 422);
            }

            $sub_dealer = $approval_action($sub_dealer_temp);
            DB::commit();
            return $this->response("00", "success", $sub_dealer);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed", $th);
        }
    }
}
