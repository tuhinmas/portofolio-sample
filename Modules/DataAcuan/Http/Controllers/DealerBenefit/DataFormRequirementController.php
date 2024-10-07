<?php

namespace Modules\DataAcuan\Http\Controllers\DealerBenefit;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DataFormRequirementController extends Controller
{
    use ResponseHandlerV2;

    public function __invoke()
    {
        try {
            $data_form = [
                "agency_levels" => DB::table('agency_levels')->whereNull("deleted_at")->select("id", "name")->orderBy("name")->get(),
                "category_products" => DB::table('product_categories')->whereNull("deleted_at")->select("id", "name")->orderBy("name")->get(),
                "gradings" => DB::table('gradings')->whereNull("deleted_at")->select("id", "name")->orderBy("name")->get(),
                "payment_methods" => DB::table('payment_methods')->whereNull("deleted_at")->select("id", "name")->orderBy("name")->get(),
            ];
            return $this->response("00", "succes", $data_form);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
