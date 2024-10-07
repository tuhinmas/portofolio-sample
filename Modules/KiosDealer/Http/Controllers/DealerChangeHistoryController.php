<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\KiosDealer\Entities\DealerDataHistory;

class DealerChangeHistoryController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected DealerChangeHistory $dealer_change_history,
        protected DealerDataHistory $dealer_data_history
    ) {
    }

    public function index(Request $request)
    {
        try {
            $data = $this->dealer_change_history
            ->with("dealer", "dealerTemp", "submitedBy.position", "confirmedBy.position", "approvedBy.position")
            ->where("dealer_id", $request->dealer_id)->get();
            return $this->response('00', 'success', $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "line" => $th->getLine(),
                "message" => "failed get dealer change history " . $th->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $currentData = $this->dealer_data_history->find($id); // Data yang dipilih saat ini

            $selectedAndPreviousData = $this->dealer_data_history->where(function ($query) use ($id, $currentData) {
                $query->where('created_at', $currentData->created_at)
                    ->orWhere('created_at', '<', $currentData->created_at);
            })
            ->with("dealer", "dealerAddress", "dealerFileHistory")
            ->where("dealer_id", $currentData->dealer_id)
            ->orderBy('created_at', 'desc')->orderByDesc("created_at")->limit(2)->get();

            return $this->response('00', 'success', $selectedAndPreviousData);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "line" => $th->getLine(),
                "message" => "failed get dealer change history " . $th->getMessage(),
            ], 500);
        }
    }
}
