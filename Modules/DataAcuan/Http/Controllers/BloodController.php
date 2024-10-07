<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Blood;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\BloodRequest;

class BloodController extends Controller
{
    use ResponseHandler;
    public function __construct(Blood $blood)
    {
        $this->blood = $blood;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $bloods = $this->blood->orderBy('name')->get();
            return $this->response('00', 'blood index', $bloods);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to get bloods index', $th->getMessage(), 500);
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
    public function store(BloodRequest $request)
    {
        try {
            $blood = $this->blood->firstOrCreate([
                "name" => $request->name,
            ]);
            return $this->response('00', 'blood saved', $blood);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save blood', $th->getMessage(), 500);
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
            $blood = $this->blood->findOrFail($id);
            return $this->response('00', 'blood detail', $blood);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display blood detail', $th->getMessage(), 500);
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
    public function update(Request $request, $id)
    {
        try {
            $blood = $this->blood->findOrFail($id);
            $blood->name = $request->name;
            $blood->save();
            return $this->response('00', 'blood updated', $blood);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update blood', $th->getMessage(), 500);
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
            $blood = $this->blood->findOrFail($id);
            $blood->delete();
            return $this->response('00', 'blood deleted', $blood);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete blood', $th->getMessage(), 500);
        }
    }
}
