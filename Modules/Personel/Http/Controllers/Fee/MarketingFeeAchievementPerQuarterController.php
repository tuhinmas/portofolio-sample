<?php

namespace Modules\Personel\Http\Controllers\Fee;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Transformers\Fee\MarketingFeeAchievementPerQuarter;

class MarketingFeeAchievementPerQuarterController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(protected Personel $personel)
    {}

    public function __invoke(Request $request)
    {
        $request->validate([
            "year" => "required",
            "quarter" => "required",
        ]);

        try {
            $personels = $this->personel->query()
                ->with([
                    "position",
                    "marketingFee" => function ($QQQ) use($request){
                        return $QQQ
                            ->with([
                                "payment",
                            ])
                            ->where("year", $request->year)
                            ->where("quarter", $request->quarter);
                    },
                ])
                ->whereHas("marketingFee", function ($QQQ) use ($request) {
                    return $QQQ
                        ->where("year", $request->year)
                        ->where("quarter", $request->quarter);
                })
                ->whereIn("status", [1,2])
                ->orderBy("name")
                ->paginate($request->limit??10);
            return new MarketingFeeAchievementPerQuarter($personels);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
