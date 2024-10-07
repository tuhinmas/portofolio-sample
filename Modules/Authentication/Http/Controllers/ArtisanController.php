<?php

namespace Modules\Authentication\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class ArtisanController extends Controller
{
    use ResponseHandler;

    public function __invoke(Request $request)
    {
        $request->validate([
            "artisan" => "required"
        ]);

        try {
            $exitCode = Artisan::call($request->artisan);
            if ($exitCode === 0) {
                return $this->response("00", "success", null);
                
            } else {
                return $this->response("00", "failed", "gagal");
            }
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
