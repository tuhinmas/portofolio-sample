<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\AgencyLevel;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\AgencyLevelRequest;

class AgencyLevelController extends Controller
{
    use ResponseHandler;

    public function __construct(AgencyLevel $agency)
    {
        $this->agency = $agency;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $agency_level = $this->agency->orderBy('name')->get();
            return $this->response('00', 'agency level index', $agency_level);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display agency level index', $th->getMessage(), 500);
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
    public function store(AgencyLevelRequest $request)
    {
        try {
            $agency_level = $this->agency->firstOrCreate([
                "name" => $request->name,
                "agency" => $request->name,
            ]);
            return $this->response('00', 'agency level saved', $agency_level);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save agency level', $th->getMessage(), 500);
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
            $agency_level = $this->agency->findOrFail($id);
            return $this->response('00', 'agency level detail', $agency_level);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to get agency level detail', $th->getMessage(), 500);
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
    public function update(AgencyLevelRequest $request, $id)
    {
        try {
            $agency_level = $this->agency->findOrFail($id);
            $agency_level->name = $request->name;
            $agency_level->agency = $request->name;
            $agency_level->save();

            return $this->response('00', 'agency level updated', $agency_level);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update agency level', $th->getMessage(), 500);
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
            $agency_level = $this->agency->findOrFail($id);
            $agency_level->delete();
            
            return $this->response('00', 'agency level deleted', $agency_level);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete agency level', $th->getMessage(), 500);
        }
    }
}
