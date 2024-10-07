<?php

namespace Modules\DataAcuan\Http\Controllers\PaymentMethod;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\PaymentMethod;

class UpdatePaymentMethodForMarketingController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(PaymentMethod $payment_method)
    {
        $this->payment_method = $payment_method;
    }

    public function __invoke(Request $request)
    {
        $request->validate([
            "set_for_marketing" => [
                "array",
                "min:1",
            ],
            "set_for_marketing.*" => [
                "required",
            ],
            "set_for_non_marketing" => [
                "array",
                "min:1",
            ],
            "set_for_non_marketing.*" => [
                "required",
            ],
        ]);

        try {
            switch (true) {
                case $request->has("set_for_marketing") && !$request->has("set_for_non_marketing"):
                    PaymentMethod::query()
                        ->whereIn("id", $request->set_for_marketing)
                        ->update([
                            "is_for_marketing" => true,
                        ]);

                    break;

                case $request->has("set_for_non_marketing") && !$request->has("set_for_marketing"):
                    PaymentMethod::query()
                        ->whereIn("id", $request->set_for_non_marketing)
                        ->update([
                            "is_for_marketing" => false,
                        ]);
                    break;

                case $request->has("set_for_non_marketing") && $request->has("set_for_marketing"):
                    PaymentMethod::query()
                        ->whereIn("id", $request->set_for_non_marketing)
                        ->update([
                            "is_for_marketing" => false,
                        ]);

                    PaymentMethod::query()
                        ->whereIn("id", $request->set_for_marketing)
                        ->update([
                            "is_for_marketing" => true,
                        ]);

                    break;

                default:
                    break;
            }

            $payment_methods = PaymentMethod::query()
                ->when($request->has("set_for_marketing"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("id", $request->set_for_marketing);
                })
                ->when($request->has("set_for_non_marketing"), function ($QQQ) use ($request) {
                    return $QQQ->orWhereIn("id", $request->set_for_non_marketing);
                })
                ->get();

            return $this->response("00", "succes", $payment_methods);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
