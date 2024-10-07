<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\BussinessSector;
use Modules\DataAcuan\Http\Requests\BusinessSectorRequest;

class BussinessSectorController extends Controller
{
    public function __construct(BussinessSector $bussiness_sector)
    {
        $this->bussiness_sector = $bussiness_sector;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $sector = $this->bussiness_sector->query()
                ->where('category_id', $request->category_id)
                ->get();
            return $this->response('00', 'Business sector  index', $sector);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Business sector', $e);
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
            $sector = $this->bussiness_sector->firstOrCreate([
                'name' => $request->sektor_usaha,
                'category_id' => $request->category_id,
            ]);
            $sector = $this->bussiness_sector->query()
                ->where('id', $sector->id)
                ->with('category')
                ->first();
            return $this->response('00', 'Business sector saved', $sector);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to save Business sector', $e);
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
            $sector = $this->bussiness_sector->query()
                ->where('id', $id)
                ->with('category')
                ->first();
            return $this->response('00', 'Business sector edit', $sector);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Business sector', $e);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = sector_id
     * @return Renderable
     */
    public function update(BusinessSectorRequest $request, $id)
    {
        try {
            $sector = $this->bussiness_sector->find($id);
            $sector->name = $request->sektor_usaha;
            $sector->category_id = $request->category_id;
            $sector->save();
            
            $sector = $this->bussiness_sector->query()
                ->where('id', $sector->id)
                ->with('category')
                ->first();

            return $this->response('00', 'Business sector updated', $sector);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to update Business sector', $e);
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
            $sector = $this->bussiness_sector->find($id);
            $sector->delete();
            return $this->response('00', 'Business sector deleted', $sector);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to delete Business sector', $e);
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
