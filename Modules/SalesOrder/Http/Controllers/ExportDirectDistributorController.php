<?php

namespace Modules\SalesOrder\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportDirectDistributor;
use Illuminate\Support\Facades\Validator;
use Modules\SalesOrder\Entities\SalesOrder;

class ExportDirectDistributorController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        SalesOrder $sales_order
    ) {
        $this->sales_order = $sales_order;
    }

    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "year" => "required"
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);    
        }

        try {
            return Excel::download(new ExportDirectDistributor($request->year), "direct sales distributor per marketing per bulan tahun $request->year.xlsx");
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
