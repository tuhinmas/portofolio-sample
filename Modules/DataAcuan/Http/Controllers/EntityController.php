<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Entity;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\EntityRequest;

class EntityController extends Controller
{
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
    use ResponseHandler;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $entities = $this->entity->all();
            return $this->response('00', 'entity index', $entities);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display entity index', $th->getMessage(), 500);
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
    public function store(EntityRequest $request)
    {
        try {
            $entity = $this->entity->firstOrCreate([
                'name' => $request->name,
            ]);
            return $this->response('00', 'entity saved' , $entity);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save entity', $th->getMessage(), 500);
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
            $entity = $this->entity->findOrFail($id);
            return $this->response('00', 'entity updated' , $entity);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to updated entity', $th->getMessage(), 500);
        }    }

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
    public function update(EntityRequest $request, $id)
    {
        try {
            $entity = $this->entity->findOrFail($id);
            $entity->name = $request->name;
            $entity->save();
            return $this->response('00', 'entity updated' , $entity);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to updated entity', $th->getMessage(), 500);
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
            $entity = $this->entity->findOrFail($id);
            $entity->delete();
            return $this->response('00', 'entity deleted' , $entity);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete entity', $th->getMessage(), 500);
        }
    }
}
