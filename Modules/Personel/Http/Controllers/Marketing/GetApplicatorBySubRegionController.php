<?php

namespace Modules\Personel\Http\Controllers\Marketing;

use App\Traits\MarketingArea;
use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Transformers\ApplicatorCCollectionResource;

class GetApplicatorBySubRegionController extends Controller
{
    use ResponseHandlerV2;
    use MarketingArea;

    public function __construct(Personel $applicator, SubRegion $sub_region)
    {
        $this->applicator = $applicator;
        $this->sub_region = $sub_region;
    }

    public function __invoke(Request $request, $sub_region_id)
    {
        try {
            $this->sub_region->findOrFail($sub_region_id);
            $applicators = $this->applicator->query()
                ->applicator()
                ->whereHas("supervisor", function ($QQQ) use ($sub_region_id) {
                    return $QQQ
                        ->whereIn("id", $this->marketingListByAreaId($sub_region_id))
                        ->whereHas("areaMarketing");
                });

            switch (true) {
                case $request->disabled_pagination:
                    $applicators = $applicators->limit($request->limit ?: 10)->get();
                    break;

                default:
                    $applicators = $applicators->paginate($request->limit ?: 10);
                    break;
            }

            return (new ApplicatorCCollectionResource($applicators));
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
