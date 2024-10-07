<?php

namespace Modules\Personel\Http\Controllers\Marketing;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\MarketingFeePayment;

class GetMarketingFeePaymentByIdController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(protected MarketingFeePayment $payment)
    {}
    public function __invoke(Request $request, $payment_id)
    {
        $this->payment->findOrFail($payment_id);
        try {
            $payment = $this->payment->query()
                ->with([
                    "marketingFee.personel.position",
                    "files",
                ])
                ->where("id" , $payment_id)
                ->first();

            $paid = DB::table('marketing_fee_payments')
                ->where("marketing_fee_id", $payment->marketing_fee_id)
                ->whereNull("deleted_at")
                ->sum("amount");

            $payment["remaining"] = ($payment->marketingFee->fee_reguler_settle + $payment->marketingFee->fee_target_settle) - $paid;
            return $this->response("00", "succes", $payment);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
