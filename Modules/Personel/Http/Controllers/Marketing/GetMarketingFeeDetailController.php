<?php

namespace Modules\Personel\Http\Controllers\Marketing;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\MarketingFeePayment;
use Modules\Personel\Entities\Personel;

class GetMarketingFeeDetailController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected MarketingFee $fee,
        protected Personel $personel,
        protected MarketingFeePayment $payment,
    ) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            "personel_id" => [
                "required",
                "min:32",
                "max:36",
            ],
            "year" => [
                "required",
                "integer",
                "digits:4",
            ],
            "quarter" => [
                "required",
                "integer",
                "min:1",
                "max:4",
            ],
        ]);

        $this->personel->findOrFail($request->personel_id);
        try {
            $marketing_fee = $this->fee->firstOrCreate([
                "personel_id" => $request->personel_id,
                "year" => $request->year,
                "quarter" => $request->quarter,
            ], [
                "fee_reguler_total" => 0,
                "fee_reguler_settle" => 0,
                "fee_reguler_settle_pending" => 0,
                "fee_target_total" => 0,
                "fee_target_settle" => 0,
                "fee_target_settle_pending" => 0,
            ]);

            /**
             * get sum total payment
             */
            $total_amount = $this->payment->query()
                ->where("marketing_fee_id", $marketing_fee->id)
                ->sum("amount");

            $total_fee = $marketing_fee->fee_reguler_settle + $marketing_fee->fee_target_settle;
            $marketing_fee->payment_remaining = $total_fee - $total_amount;
            $marketing_fee->payment_status = ($total_amount == 0 ? 1 : ($total_amount >= ($total_fee) ? 3 : 2));
            $marketing_fee = collect($marketing_fee)->forget(["region", "sub_region"]);

            return $this->response("00", "success", $marketing_fee);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
