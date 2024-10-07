<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\BussinessSectorCategory;
use Modules\DataAcuan\Http\Requests\BusinessSectorRequest;

class BussinessSectorCategoryController extends Controller
{
    public function __construct(BussinessSectorCategory $bussiness_sector_category)
    {
        $this->bussiness_sector_category = $bussiness_sector_category;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $category = $this->bussiness_sector_category->query()
                ->with('sector')
                ->get();
            return $this->response('00', 'Business sector category index', $category);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Business sector category', $e);
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
    public function store(BusinessSectorRequest $request)
    {
        try {
            $category = $this->bussiness_sector_category->firstOrCreate([
                'name' => $request->sektor_usaha,
            ]);
            
            $category = $this->bussiness_sector_category->query()
                ->with('sector')
                ->where('id', $category->id)
                ->first();
            return $this->response('00', 'Business sector category saved', $category);
        } catch (\Exception $e) {
            return $this->response('01', 'Failed to save Business sector category', $e);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('dataacuan::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $category = $this->bussiness_sector_category->find($id);
            return $this->response('00', 'Business sector category edit', $category);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Business sector category', $e);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(BusinessSectorRequest $request, $id)
    {
        try {
            $category = $this->bussiness_sector_category->find($id);
            $category->name = $request->sektor_usaha;
            $category->save();

            $category = $this->bussiness_sector_category->query()
                ->with('sector')
                ->where('id', $id)
                ->first();
            return $this->response('00', 'Business sector category updated', $category);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to update Business sector category', $e);
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
            $category = $this->bussiness_sector_category->find($id);
            $category->delete();
            return $this->response('00', 'Business sector category deleted', $category);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to delete Business sector category', $e);
        }
    }

    /**
     * response
     *
     * @param [type] $code
     * @param [type] $message
     * @param [type] $data
     * @return void
     */
    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }
}
