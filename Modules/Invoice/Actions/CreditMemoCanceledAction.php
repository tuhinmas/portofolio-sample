<?php

namespace Modules\Invoice\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Invoice\Entities\CreditMemoHistory;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Jobs\CreditMemoCanceledJob;

class CreditMemoCanceledAction
{
    /**
     * cancelation memo include
     * 1. make gitory status from credit memo
     * 2. payment remaining update, and proforma payment status rules
     * 3. if all memo canceled, return destination to confirm and recalculate fee point
     *
     * @param CreditMemo $credit_memo
     * @return void
     */
    public function __invoke(CreditMemo $credit_memo)
    {
        if ($credit_memo->status != "canceled") {
            return;
        }

        /**
         * -------------------------------------------------------
         * Make history of memo
         * ---------------------------------------------
         */
        CreditMemoHistory::create([
            "personel_id" => auth()->user()->personel_id,
            "credit_memo_id" => $credit_memo->id,
            "status" => $credit_memo->status,
        ]);

        CreditMemoCanceledJob::dispatch($credit_memo, auth()->user());

        $credit_memo->unsetRelation("destination");
    }
}
