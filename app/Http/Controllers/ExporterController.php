<?php

namespace App\Http\Controllers;

use App\Traits\Uuids;
use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use App\Exports\SubRegionExport;
use Maatwebsite\Excel\Facades\Excel;

class ExporterController extends Controller
{
    use ResponseHandler;


    public function subRegionExport(){
        try {
            $data = Excel::store(new SubRegionExport, 'sub_region.xlsx');
            return $this->response("00", "success export sub region", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed export sub region", $th->getMessage());
        }
    }
}
