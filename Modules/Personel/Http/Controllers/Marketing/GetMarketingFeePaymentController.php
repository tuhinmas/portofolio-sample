<?php

namespace Modules\Personel\Http\Controllers\Marketing;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\MarketingFeePayment;

class GetMarketingFeePaymentController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected MarketingFeePayment $payment,
        protected MarketingFee $fee,
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
        ]);

        $request->merge([
            "direction" => $request->direction ?? "asc",
            "sort_by" => $request->sort_by ?? "quarter",
        ]);

        try {
            $marketing_fee = DB::table('marketing_fee')
                ->whereNull("deleted_at")
                ->where("personel_id", $request->personel_id)
                ->where("year", $request->year)
                ->count();

            if ($marketing_fee != 4) {
                for ($i = 1; $i < 5; $i++) {
                    $this->fee->firstOrCreate([
                        "personel_id" => $request->personel_id,
                        "year" => $request->year,
                        "quarter" => $i,
                    ]);
                }
            }

            $marketing_fee = $this->fee->query()
                ->with([
                    "payment" => function ($QQQ) {
                        return $QQQ->with([
                            "personel.position",
                        ]);
                    },
                    "lastPayment" => function ($QQQ) {
                        return $QQQ->with([
                            "personel.position",
                        ]);
                    },
                ])
                ->where("personel_id", $request->personel_id)
                ->where("year", $request->year)
                ->get()
                ->map(function ($fee) {
                    $fee["fee_achieved"] = $fee->fee_reguler_settle + $fee->fee_target_settle;
                    $fee["fee_paid_amount"] = $fee?->payment->sum("amount");
                    $fee["fee_last_payment"] = $fee?->lastPayment?->date;
                    $fee["fee_last_reporter"] = $fee?->lastPayment?->personel;
                    $fee = collect($fee)->forget(["region", "sub_region", "last_payment"]);
                    return $fee;
                });
            return $this->response("00", "success", self::sortBy($marketing_fee, $request->sort_by, $request->direction));
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }

    public static function sortBy(Collection $payments, $sort_by, $direction)
    {
        $direction = match ($direction) {
            "desc" => "sortByDesc",
            default => "sortBy"
        };

        switch ($sort_by) {
            case 'fee_last_reporter':
                return $payments
                    ->{$direction}(function ($payment) use ($sort_by) {
                        if (isset($payment[$sort_by])) {
                            return $payment[$sort_by]->name;
                        }
                    })
                    ->values();
                break;

            default:
                return $payments
                    ->{$direction}(function ($payment) use ($sort_by) {
                        return $payment[$sort_by];
                    })
                    ->values();
                break;
        }
    }
}
