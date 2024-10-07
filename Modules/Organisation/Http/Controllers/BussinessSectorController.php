<?php

namespace Modules\Organisation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\BussinessSector;
use Modules\Organisation\Entities\Organisation;

class BussinessSectorController extends Controller
{
    public function __construct(BussinessSector $bussiness_sector, Organisation $organisation)
    {
        $this->bussiness_sector = $bussiness_sector;
        $this->organisation = $organisation;
    }
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $id = $request->organisation_id;
            $organisation = $this->organisation->findOrFail($id);
            $data = $organisation->bussiness_sector()->get();
            return $this->response('00', 'Organisation sector index', $data);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display organisation index', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('organisation::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            // $organisation = $this->organisation->findOrfail($request->organisation_id);
            // $bussiness_sector = [$request->bussiness_sector];
            // $organisation->bussiness_sector()->sync($bussiness_sector);
            // $organisation = $organisation->query()
            //     ->where('id', $organisation->id)
            //     ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
            //     ->first();

            $sector = $this->bussiness_sector->findOrFail($request->bussiness_sector);
            $sector->bussiness_organisation()->sync($request->organisation_id);

            $organisation = $this->organisation->findOrfail($request->organisation_id);
            $organisation = $organisation->query()
                ->where('id', $organisation->id)
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->first();
            return $this->response('00', 'organisation business sector saved', $organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to save organisation business sector', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id = sector id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $bussiness_sector = $this->bussiness_sector->query()
                ->where('id', $id)
                ->with('category')
                ->first();
            return $this->response('00', 'Organisation sector detail', $bussiness_sector);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display organisation sector detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id = sector id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $bussiness_sector = $this->bussiness_sector->query()
                ->where('id', $id)
                ->with('category')
                ->first();
            return $this->response('00', 'Organisation sector edit', $bussiness_sector);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display organisation edit', $th->GetMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = sector id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $sector = $this->bussiness_sector->findOrFail($id);
            $sector->bussiness_organisation()->updateExistingPivot($request->organisation_id, [
                'bussiness_sector_id' => $request->bussiness_sector,
            ]);
            $sector = $this->bussiness_sector->findOrFail($request->bussiness_sector);
            $sector->bussiness_organisation()->sync($request->organisation_id);

            $organisation = $this->organisation->findOrfail($request->organisation_id);
            $organisation = $organisation->query()
                ->where('id', $organisation->id)
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->first();
            return $this->response('00', 'Organisation sector updated', $organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update organisation sector', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id = sector id
     * @return Renderable
     */
    public function destroy(Request $request, $id)
    {
        try {
            $sector = $this->bussiness_sector->findOrFail($id);
            $sector->bussiness_organisation()->detach($request->organisation_id);
            $organisation = $this->organisation->findOrfail($request->organisation_id);
            $organisation = $organisation->query()
                ->where('id', $organisation->id)
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->first();
            return $this->response('00', 'Organisation sector deleted', $organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to delete organisation sector', $th->getMessage());
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
