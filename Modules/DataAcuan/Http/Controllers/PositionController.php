<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\PositionRequest;

class PositionController extends Controller
{
    public function __construct(Position $position,Personel $personel, Role $role)
    {
        $this->position = $position;
        $this->personel = $personel;
        $this->role = $role;
        // $this->middleware('can:crud data acuan')->except("index");
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $positions = $this->position->query()
                ->where('name', 'like' , '%' . $request->name .'%')
                ->with('division')
                ->orderBy('name')
                ->when($request->is_for_event, function($QQQ){
                    return $QQQ->eventCreator();
                })
                ->get();
            return $this->response('00', 'Position index', $positions);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Position', $e->getMessage());
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
    public function store(PositionRequest $request)
    {
        try {
            $position = $this->position->firstOrCreate([
                'name' => $request->jabatan,
                'division_id' => $request->divisi,
            ], [
                'job_description' => $request->deskripsi_pekerjaan,
                'job_definition' => $request->definisi_pekerjaan,
                'job_specification' => $request->spesifikasi_pekerjaan,
            ]);
            $role = $this->role->firstOrCreate([
                "name" => $position->name
            ]);
            $role->givePermissionTo("see own profile");

            $position = $this->position->query()
                ->where('id', $position->id)
                ->with('division', 'role', 'role.permissions')
                ->first();
            

            return $this->response('00', 'Position saved', $position);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to save Position', $e->getMessage());
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
            $position = $this->position->query()
                ->where('id', $id)
                ->with('division', 'role', 'role.permissions')
                ->first();
            return $this->response('00', 'Position edit', $position);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Position', $e->getMessage());
        }    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $position = $this->position->query()
                ->where('id', $id)
                ->with('division')
                ->first();
            return $this->response('00', 'Position edit', $position);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display Position', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(PositionRequest $request, $id)
    {
        try {
            $position = $this->position->findOrFail($id);
            $position->name = $request->jabatan;
            $position->division_id = $request->divisi;
            $position->job_description = $request->deskripsi_pekerjaan;
            $position->job_definition = $request->definisi_pekerjaan;
            $position->job_specification = $request->spesifikasi_pekerjaan;
            $position->save();

            $position = $this->position->query()
                ->where('id', $id)
                ->with('division')
                ->first();
            return $this->response('00', 'Position updated', $position);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to update Position', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {       
        $check = $this->personel->where('position_id',$id)->first();
        if($check){
            return $this->response('02', 'Failed to delete Position', $check);
        }
        try {
            $position = $this->position->query()
                ->where('id', $id)
                ->with('division', 'role', 'role.permissions')
                ->first();
            $position->destroy($id);
            $role_to_delete = $this->role->findByName($position->name);
            if ($role_to_delete) {
                $role_to_delete->delete();
            }
            return $this->response('00', 'Position deleted', $position);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to delete Position', $e->getMessage());
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
