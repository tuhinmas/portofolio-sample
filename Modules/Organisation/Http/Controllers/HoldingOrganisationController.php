<?php

namespace Modules\Organisation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organisation\Entities\Organisation;

class HoldingOrganisationController extends Controller
{
    public function __construct(Organisation $organisation)
    {
        $this->organisation = $organisation;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $holding_organisation = $this->organisation->query()
                ->where('holding_id', $request->holding_id)
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->get();
            return $this->response('00', 'holding member', $holding_organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display holding member', $th->getMessage());
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
            $organisation_holding = $this->organisation->findOrFail($request->organisation_id);
            $organisation_holding->holding_id = $request->holding_id;
            $organisation_holding->save();
            return $this->response('00', 'holding member saved', $organisation_holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save member holding', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('organisation::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id = organisation_id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $organisation_holding = $this->organisation->findOrFail($id);
            return $this->response('00', 'holding member edit', $organisation_holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display member holding', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = organisation
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $organisation_holding = $this->organisation->findOrFail($id);
            $organisation_holding->holding_id = $request->holding_id;
            $organisation_holding->save();
            return $this->response('00', 'holding member updated', $organisation_holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update member holding', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id = organisation_id
     * @return Renderable
     */
    public function destroy(Request $request, $id)
    {
        try {
            $organisation_holding = $this->organisation->findOrFail($id);
            $organisation_holding->holding_id = null;
            $organisation_holding->save();
            return $this->response('00', 'Holding organisation deleted', $organisation_holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete holding organisation', $th->getMessage());
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
