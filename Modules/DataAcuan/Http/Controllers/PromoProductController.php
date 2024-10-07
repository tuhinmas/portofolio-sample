<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Http\Requests\PromoProductRequest;
use Modules\DataAcuan\Http\Requests\PromoProductUpdateRequest;
use Modules\DataAcuan\Repositories\PromoProductRepository;

class PromoProductController extends Controller
{
    use ResponseHandler;
   
    public function index(Request $request, $promoId)
    {
        try {
            return 'oke';
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get promo product index', $th->getMessage(), 500);
        }
    }

    public function create()
    {
        return view('dataacuan::create');
    }

    public function store(PromoProductRequest $request, $promoId)
    {
        try {
            $promoProduct = new PromoProductRepository();
            $response = $promoProduct->storeOrUpdate($request->data(), $promoId);
            return $this->response("00", "promo product saved", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save rhesus", $th->getMessage(), 500);
        }
    }

    public function list(Request $request, $promoId)
    {
        try {
            $promoProduct = new PromoProductRepository();
            $product = $promoProduct->listProductByPromo($request->all(), $promoId);
            $dataArray = $product; // Your array of data
            $page = request()->get('page', 1);
            $perPage = 15; // Number of items per page
            $totalItems = count($dataArray);
            $totalPages = ceil($totalItems / $perPage);
            $offset = ($page - 1) * $perPage;
            $currentPageItems = array_slice($dataArray, $offset, $perPage);
            $paginationLinks = [];
            for ($i = 1; $i <= $totalPages; $i++) {
                $paginationLinks[] = [
                    'page' => $i,
                    'url' => '?page=' . $i,
                    'active' => $i == $page,
                ];
            }

            $promo = $promoProduct->showPromo($promoId);
            $response = [
                'id' => $promo->id,
                'name' => $promo->name,
                'date_start' => $promo->date_start,
                'date_end' => $promo->date_end,
                'promo_product_list' => $currentPageItems
            ];
            return $this->response("00", "List Promo Product", $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get promo product index', $th->getMessage(), 500);
        }
    }

    public function update(PromoProductUpdateRequest $request, $promoId, $productId)
    {
        try {
            $promoProduct = new PromoProductRepository();
            $response = $promoProduct->storeOrUpdate($request->data(), $promoId);
            return $this->response("00", "promo product saved", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save rhesus", $th->getMessage(), 500);
        }
    }

    public function delete($promoId, $productId)
    {
        try {
            $promoProduct = new PromoProductRepository();
            $delete = $promoProduct->delete($promoId, $productId);
            return $this->response("00", "promo product deleted", []);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save rhesus", $th->getMessage(), 500);
        }
    }

    public function saveAttributeProduct(Request $request, $promoId)
    {
        try {
            $promoProduct = new PromoProductRepository();
            $response = $promoProduct->saveAttributeProduct($request->all(), $promoId);
            return $this->response("00", "promo product saved", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save rhesus", $th->getMessage(), 500);
        }
    }

}
