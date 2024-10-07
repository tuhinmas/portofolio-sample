<?php

namespace Modules\Personel\Http\Controllers\Marketing;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Transformers\ApplicatorCCollectionResource;

class GetApplicatorByMarketingController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(Personel $personel)
    {
        $this->personel = $personel;
    }

    public function __invoke(Request $request, $id)
    {
        $this->personel->findOrFail($id);

        try {
            $applicators = $this->personel->query()
                ->with([
                    "position",
                ])
                ->where("supervisor_id", $id)
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->where("name", "Aplikator");
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
