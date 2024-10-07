<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\BloodRhesus;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\BloodRhesusRequest;

class BloodRhesusController extends Controller
{
    use ResponseHandler;
    public function __construct(BloodRhesus $rhesus)
    {
        $this->rhesus = $rhesus;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $rhesus = $this->rhesus->orderBy("name")->get();
            return $this->response('00', 'blood rhesus index', $rhesus);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get blood rhesus index', $th->getMessage(), 500);
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
    public function store(BloodRhesusRequest $request)
    {
        try {
            $rhesus = $this->rhesus->firstOrCreate([
                "name" => $request->name
            ]);
            return $this->response("00", "rhesus saved", $rhesus);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save rhesus", $th->getMessage(), 500);
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
            $rhesus = $this->rhesus->findOrFail($id);
            return $this->response("00", "rhesus detail", $rhesus);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to display rhesus deatil", $th->getMessage(), 500);
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
            $rhesus = $this->rhesus->findOrFail($id);
            $rhesus->name = $request->name;
            $rhesus->save();
            return $this->response("00", "rhesus updated", $rhesus);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to update rhesus", $th->getMessage(), 500);
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
            $rhesus = $this->rhesus->findOrFail($id);
            $rhesus->delete();
            return $this->response("00", "rhesus delete", $rhesus);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to delete rhesus", $th->getMessage(), 500);
        } 
    }
}
