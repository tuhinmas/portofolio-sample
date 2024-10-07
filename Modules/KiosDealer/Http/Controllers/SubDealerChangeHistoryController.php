<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\KiosDealer\Entities\SubDealerChangeHistory;
use Modules\KiosDealer\Entities\SubDealerDataHistory;

class SubDealerChangeHistoryController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SubDealerChangeHistory $sub_dealer_change_history,
        protected SubDealerDataHistory $sub_dealer_data_history
    ) {
    }

    public function index(Request $request)
    {
        try {
            $data = $this->sub_dealer_change_history
            ->with("subDealer", "subDealerTemp", "submitedBy.position", "confirmedBy.position", "approvedBy.position")
            ->where("sub_dealer_id", $request->sub_dealer_id)->get();
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
            
            $currentData = $this->sub_dealer_data_history->find($id); // Data yang dipilih saat ini
            // dd($currentData);
            $selectedAndPreviousData = $this->sub_dealer_data_history->where(function ($query) use ($id, $currentData) {
                $query->where('created_at', $currentData->created_at)
                    ->orWhere('created_at', '<', $currentData->created_at);
            })
            ->with("subDealer", "subDealerAddress", "subDealerFileHistory")
            ->where("sub_dealer_id", $currentData->sub_dealer_id)
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
