<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Religion;

class ReligionController extends Controller
{
    public function __construct(Religion $religion)
    {
        $this->religion = $religion;
        // $this->middleware('can:crud data acuan')->except("index");
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $religion = $this->religion->all();
            return $this->response('00','Religion index', $religion);
        } catch (\Exception $e) {
            return $this->response('01','failed to show religion', $e);
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
    public function store(Request $request)
    {
        try {
            $religion = $this->religion->firstOrCreate([
                'name' => $request->agama,
            ]);
            
            return $this->response('00','Religion saved', $religion);
        } catch (\Exception $e) {
            return $this->response('01','failed to save', $e);
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
            $religion = $this->religion->find($id);
            return $this->response('00','Religion edit', $religion);
        } catch (\Exception $e) {
            return $this->response('01','failed to show data', $e);
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
        try {
            $religion = $this->religion->find($id);
            $religion->name = $request->agama;
            $religion->save();

            return $this->response('00','Religion update', $religion);
        } catch (\Exception $e) {
            return $this->response('01','failed to update religion', $e);
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
            $religion = $this->religion->find($id);
            $religion->delete();

            return $this->response('00','Religion deleted', $religion);
        } catch (\Exception $e) {
            return $this->response('01','failed to delete religion', $e);
        }
    }

    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }
}
