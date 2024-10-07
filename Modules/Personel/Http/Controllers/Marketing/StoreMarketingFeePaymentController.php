<?php

namespace Modules\Personel\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\MarketingFeeFile;
use Modules\Personel\Entities\MarketingFeePayment;
use Modules\Personel\Rules\Marketing\MarketingFeePaymentLinktRule;
use Modules\Personel\Rules\Marketing\MarketingFeePaymenAmounttRule;
use Modules\Personel\Rules\Marketing\MarketingFeePyamentReporterRule;

class StoreMarketingFeePaymentController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected MarketingFeePayment $payment,
        protected MarketingFeeFile $file,
        protected MarketingFee $fee,
    ) {}

    public function __invoke(Request $request)
    {
        $previous = now()->year - 1;
        $current = now()->year;
        $request->validate([
            "personel_id" => [
                "required",
                "min:32",
                "max:36",
                new MarketingFeePyamentReporterRule
            ],
            "year" => [
                "required",
                "integer",
                "digits:4",
                "between:$previous,$current",
            ],
            "quarter" => [
                "required",
                "integer",
                "min:1",
                "max:4",
            ],
            "amount" => [
                "required",
                "numeric",
            ],
            "reference_number" => [
                "required",
                "string",
                new MarketingFeePaymenAmounttRule,
            ],
            "date" => [
                "required",
                'date_format:Y-m-d H:i:s',
            ],
            "note" => [
                "nullable",
                "string",
                "max:255",
            ],
            "link" => [
                "string",
                "nullable",
                new MarketingFeePaymentLinktRule,
            ],
            "caption" => [
                "string",
                "nullable",
            ],
        ]);

        try {
            $marketing_fee = $this->fee->firstOrCreate([
                "personel_id" => $request->personel_id,
                "year" => $request->year,
                "quarter" => $request->quarter,
            ]);

            /**
             * get sum total payment
             */
            $total_amount = $this->payment->query()
                ->where("marketing_fee_id", $marketing_fee->id)
                ->sum("amount");

            if (($total_amount + $request->amount) > ($marketing_fee->fee_reguler_settle + $marketing_fee->fee_target_settle)) {
                return $this->response("04", "invalid data send", [
                    "message" => [
                        "pembayaran melebihi jumlah fee yang didapatkan marketing",
                    ],
                ], 422);
            }

            /**
             * make payment report
             */
            $payment = $this->payment->firstOrCreate([
                "reference_number" => $request->reference_number,
                "marketing_fee_id" => $marketing_fee->id,
            ], [
                "personel_id" => auth()->user()?->personel_id,
                "status" => 1,
                "amount" => $request->amount,
                "date" => $request->date,
                "note" => $request->note,
            ]);

            /**
             * save attachment file
             */
            if ($request->link) {
                $this->file->firstOrCreate([
                    "marketing_fee_payment_id" => $payment->id,
                ], [
                    "link" => $request->link,
                    "caption" => $request->caption,
                ]);
            }

            return $this->response("00", "success", $payment);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
