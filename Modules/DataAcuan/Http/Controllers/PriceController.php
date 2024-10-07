<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\Product;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Http\Requests\PriceRequest;

class PriceController extends Controller
{
    use ResponseHandler;

    public function __construct(Product $product, Price $price)
    {
        $this->product = $product;
        $this->price = $price;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $products = $this->product->query()
                ->with('allPackage', 'priceCheapToExpensive','package')
                ->withCount("priceCheapToExpensive")
                ->where('name', 'like', '%' . $request->name . '%')
                ->whereIn('category', $this->category($request))
                ->when($request->category_name, function ($QQQ) use ($request) {
                    if (!in_array("special", $request->category_name)) {
                        return $QQQ->whereIn("category", $this->category($request));
                    } else {
                        return $QQQ->whereIn("category", $this->category($request));
                    }
                })
                ->when($request->has("is_active"), function ($QQQ) use ($request) {
                    return $QQQ->where("is_active", $request->is_active);
                })
                ->when($request->has("sort_by"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
                    if ($request->sort_by == 'product_name') {
                        return $QQQ->orderBy("name", $sort_type);
                    } elseif ($request->sort_by == 'category_name') {
                        return $QQQ->withAggregate("category", "name")
                        ->orderByRaw("category_name {$sort_type}");
                    } elseif ($request->sort_by == 'unit') {
                        return $QQQ->orderBy("unit", $sort_type);
                    } elseif ($request->sort_by == 'packaging') {
                        return $QQQ->withAggregate("package", "quantity_per_package")
                        ->orderByRaw("package_quantity_per_package {$sort_type}");
                    } elseif ($request->sort_by == 'sales_packaging') {
                        return $QQQ->withAggregate("package", "packaging")
                        ->orderByRaw("package_packaging {$sort_type}");
                    } else {
                        return $QQQ->orderBy("name", "asc");
                    }
                })

                /* filter by price */
                ->when($request->has("product_price"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("price", function ($QQQ) use ($request) {
                        return $QQQ->where("price", ">=", $request->product_price);
                    });
                })
                
                ->paginate(30);
            return $this->response('00', 'Prices index', $products);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display Prices', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('dataacuan::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(PriceRequest $request)
    {
        try {
            $price = $this->price->firstOrCreate([
                "product_id" => $request->product_id,
                "agency_level_id" => $request->agency_level_id,
                "het" => $request->het,
                "price" => $request->price,
                "minimum_order" => $request->minimum_order,
            ]);
            // $price = $price->with("product", "agency_Level")->first();
            return $this->response('00', 'price saved', $price);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save price', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $price = $this->price->query()
                ->where('id', $id)
                ->with("product", "agency_Level")
                ->first();
            return $this->response('00', 'price detail', $price);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display price detail', $th->getMessage());
        } 
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
          
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(PriceRequest $request, $id)
    {
        try {
            $price = $this->price->where('id', $id)
                ->update([
                    "product_id" => $request->product_id,
                    "agency_level_id" => $request->agency_level_id,
                    "het" => $request->het,
                    "price" => $request->price,
                    "minimum_order" => $request->minimum_order,
                ]);
            $price = $this->price->query()
                ->where('id', $id)
                ->with("product", "agency_Level")
                ->first();
            return $this->response('00', 'price updated', $price);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update price', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $price = $this->price->findOrFail($id);
            $price->delete();
            return $this->response('00', 'price deleted', $price);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete price', $th->getMessage());
        } 
    }

    public function category($request)
    {
        $categories = $request->category;
        $category = DB::table('product_categories')->whereNull("deleted_at")->get();
        $category_id_list = collect($category)->pluck("id");

        if (!$request->category) {
            $categories = $category_id_list;
        }

        if ($request->category_name) {
            $categories = collect($category)->whereIn("name", $request->category_name)->pluck("id")->toArray();
        }
        return $categories;
    }
}
