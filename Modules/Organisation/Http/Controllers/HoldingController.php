<?php

namespace Modules\Organisation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organisation\Entities\Holding;
use Illuminate\Contracts\Support\Renderable;
use Modules\Organisation\Entities\Organisation;

class HoldingController extends Controller
{
    public function __construct(Holding $holding, Organisation $organisation)
    {
        $this->holding = $holding;
        $this->organisation = $organisation;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $holdings = $this->holding->query()
                ->with('organisation')
                ->orderBy('name')
                ->get();
            return $this->response('00', 'holding index', $holdings);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display holding', $th->getMessage());
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
        $request->validate([
            'name' => 'required',
            'tanggal_berdiri' => 'required',
        ]);

        try {
            $holding = $this->holding->firstOrCreate([
                'name' => $request->name,
                'date_standing' => $request->tanggal_berdiri,
                'note' => $request->note,
            ]);
            return $this->response('00', 'holding saved', $holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to save holding', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $holding = $this->holding->findOrFail($id);
            return $this->response('00', 'holding edit', $holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display holding edit', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'tanggal_berdiri' => 'required',
        ]);

        try {
            $holding = $this->holding->findOrFail($id);
            $holding->name = $request->name;
            $holding->date_standing = $request->tanggal_berdiri;
            $holding->note = $request->note;
            $holding->save();
            return $this->response('00', 'holding updated', $holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update holding', $th->getMessage());
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
            $holding = $this->holding->findOrFail($id);
            $holding->delete();
            $this->organisation->where("holding_id", $id)->update([
                "holding_id" => null,
            ]);
            
            return $this->response('00', 'holding deleted', $holding);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update holding', $th->getMessage());
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
