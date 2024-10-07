<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\ProductCategory;
use Modules\DataAcuan\Http\Requests\ProductCategoryRequest;

class ProductCategoryController extends Controller
{
    use ResponseHandler;

    public function __construct(ProductCategory $category)
    {
        $this->category = $category;
        // $this->middleware('role:administrator|super-admin|admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)')->except("index");
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $categories = $this->category->query()
                ->where("name", "like", $request->name. '%')
                ->orderBy("name")
                ->get();
            return $this->response('00', 'product category index', $categories);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to get product category index', $th->getMessage());
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
    public function store(ProductCategoryRequest $request)
    {
        try {
            $category = $this->category->firstOrCreate([
                "name" => $request->name,
            ]);
            return $this->response('00', 'product category saved', $category);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save product category', $th->getMessage());
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
            $category = $this->category->findOrFail($id);
            return $this->response('00', 'product category detail', $category);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to get product category detail', $th->getMessage());
        }    
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('dataacuan::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(ProductCategoryRequest $request, $id)
    {
        try {
            $category = $this->category->findOrFail($id);
            $category->name = $request->name;
            $category->save();
            return $this->response('00', 'product category updated', $category);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update product category', $th->getMessage());
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
            $category = $this->category->findOrFail($id);
            $category->delete();
            return $this->response('00', 'product category deleted', $category);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete product category', $th->getMessage());
        }   
    }
}
