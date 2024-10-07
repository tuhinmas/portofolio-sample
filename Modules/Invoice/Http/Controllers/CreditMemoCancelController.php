<?php

namespace Modules\Invoice\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Actions\CreditMemoCanceledAction;
use Modules\Invoice\Entities\CreditMemo;
use App\Traits\ResponseHandler;

class CreditMemoCancelController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected CreditMemo $credit_memo
    ) {}

    public function __invoke(CreditMemoCanceledAction $credit_memo_cancel_action, Request $request, $credit_memo_id)
    {
        $credit_memo = $this->credit_memo->findOrFail($credit_memo_id);
        $request->validate([
            "cancelation_note" => "required|max:255|min:5",
        ]);

        try {
            DB::beginTransaction();
            $credit_memo->status = "canceled";
            $credit_memo->cancelation_note = $request->cancelation_note;
            $credit_memo->save();
            $credit_memo_cancel_action($credit_memo);

            DB::commit();
            return $this->response("00", "success", $credit_memo);

        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed", $th);
        }
    }
}
