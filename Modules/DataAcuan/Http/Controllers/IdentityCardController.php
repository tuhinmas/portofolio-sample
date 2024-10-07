<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\IdentityCard;
use Modules\DataAcuan\Http\Requests\IdentityCardRequest;

class IdentityCardController extends Controller
{
    use ResponseHandler;

    public function __construct(IdentityCard $identity){
        $this->identity = $identity;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $identities = $this->identity->orderBy('name')->get();
            return $this->response('00', 'identity card index', $identities);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get identity card index', $th->getMessage(), 500);
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
    public function store(IdentityCardRequest $request)
    {
        try {
            $identity = $this->identity->firstOrCreate([
                "name" => $request->name,
            ]);
            return $this->response('00', 'identity card saved', $identity);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to save identity card', $th->getMessage(), 500);
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
            $identity = $this->identity->findOrFail($id);
            return $this->response('00', 'identity card detail', $identity);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get identity card detail', $th->getMessage(), 500);
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
    public function update(IdentityCardRequest $request, $id)
    {
        try {
            $identity = $this->identity->findOrFail($id);
            $identity->name = $request->name;
            $identity->save();
            return $this->response('00', 'identity card updated', $identity);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update identity card', $th->getMessage(), 500);
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
            $identity = $this->identity->findOrFail($id);
            $identity->delete();
            return $this->response('00', 'identity card deleted', $identity);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to delete identity card', $th->getMessage(), 500);
        }
    }
}
