<?php

namespace Modules\Personel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Modules\Personel\Repositories\PersonelRepository;

class MarketingListController extends Controller
{
    use ResponseHandler;

    protected $personelRepository;

    public function __construct(PersonelRepository $personelRepository)
    {
        $this->personelRepository = $personelRepository;
    }

    public function index(Request $request)
    {
        try {
            $response = $this->personelRepository->marketingList($request);
            return $this->response("00", "Success", $response);
        } catch (\Exception $th) {
            return $this->response("01", "failed to get marketing index", $th, 500);
        }
    }

}