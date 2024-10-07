<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\CapitalStatus;
use Modules\DataAcuan\Http\Requests\CapitalStatusRequest;

class CapitalStatusController extends Controller
{
    use ResponseHandler;

    public function __construct(CapitalStatus $capital){
        $this->capital = $capital;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $capitals = $this->capital->orderBy("name")->get();
            return $this->response('00','capital status index', $capitals);
        } catch (\Throwable $th) {
            return $this->response('01','failed to get capital status index', $th->getMessage(), 500);
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
    public function store(CapitalStatusRequest $request)
    {
        try {
            $capital = $this->capital->firstOrCreate([
                'name' => $request->name
            ]);
            return $this->response('00','capital status saved', $capital);
        } catch (\Throwable $th) {
            return $this->response('01','failed to save capital status', $th->getMessage(), 500);
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
            $capital = $this->capital->findOrFail($id);
            return $this->response('00','capital status saved', $capital);
        } catch (\Throwable $th) {
            return $this->response('01','failed to get capital status detail', $th->getMessage(), 500);
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
    public function update(CapitalStatusRequest $request, $id)
    {
        try {
            $capital = $this->capital->findOrFail($id);
            $capital->name= $request->name;
            $capital->save();
            return $this->response('00','capital status updated', $capital);
        } catch (\Throwable $th) {
            return $this->response('01','failed to update capital status', $th->getMessage(), 500);
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
            $capital = $this->capital->findOrFail($id);
            $capital->delete();
            return $this->response('00','capital status delete', $capital);
        } catch (\Throwable $th) {
            return $this->response('01','failed to delete capital status', $th->getMessage(), 500);
        } 
    }
}
