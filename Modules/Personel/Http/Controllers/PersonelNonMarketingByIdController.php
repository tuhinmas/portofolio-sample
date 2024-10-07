<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;

class PersonelNonMarketingByIdController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(protected Personel $personel)
    {}

    public function __invoke(Request $request, $personel_id)
    {
        try {
            $personel = $this->personel->query()
                ->with([
                    "contact",
                    "address",
                    "position",
                    "religion",
                    "supervisor",
                    "citizenship",
                    "identityCard",
                    "organisation",
                    "personelBanks.bank",
                ])
                ->findOrFail($personel_id);

            return $this->response("00", "succes", $personel);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
