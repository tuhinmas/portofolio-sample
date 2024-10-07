<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\OrganisationCategory;
use Modules\DataAcuan\Http\Requests\CategoryRequest;

class OrganisationCategoryController extends Controller
{
    use ResponseHandler;

    public function __construct(OrganisationCategory $category){
        $this->category = $category;
        // $this->middleware("role:administrator|super-admin|admin")->except("index");
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $categories = $this->category->orderBy("name")->get();
            return $this->response('00','organisation category', $categories);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get organisation categories', $th->getMessage());
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
    public function store(CategoryRequest $request)
    {
        try {
            $category = $this->category->firstOrCreate([
                "name" => $request->name,
            ]);

            return $this->response('00','organisation category saved', $category);
        } catch (\Throwable $th) {
            return $this->response('01','failed to save organisation category', $th->getMessage());
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
            return $this->response('00','organisation category detail', $category);
        } catch (\Throwable $th) {
            return $this->response('01','failed to display organisation category detail', $th->getMessage());
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
    public function update(CategoryRequest $request, $id)
    {
        try {
            $category = $this->category->findOrFail($id);
            $category->name = $request->name;
            $category->save();
            return $this->response('00','organisation category updated', $category);
        } catch (\Throwable $th) {
            return $this->response('01','failed to update organisation category', $th->getMessage());
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
            return $this->response('00','organisation category deleted', $category);
        } catch (\Throwable $th) {
            return $this->response('01','failed to delete organisation category', $th->getMessage());
        }  
    }
}
