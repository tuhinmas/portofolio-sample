<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Traits\SelfReferenceTrait;

class DivisionController extends Controller
{
    use SelfReferenceTrait;

    public function __construct(Division $division)
    {
        $this->division = $division;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $divisions = $this->division->query()
                ->with('induk_divisi')
                ->get();
            return $this->response('00', 'Division index', $divisions);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display division', $e->getMessage());
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
            $division = $this->division->firstOrCreate([
                'name' => $request->name,
            ], [
                'description' => $request->description,
                'parent_id' => $request->induk_divisi,
            ]);
            $division = $this->division->query()
                ->where('id', $division->id)
                ->with('induk_divisi')
                ->first();

            return $this->response('00', 'Division saved', $division);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to save division', $e->getMessage());
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
            $division = $this->division->query()
                ->where('id', $id)
                ->with('induk_divisi')
                ->first();
            return $this->response('00', 'Division edit', $division);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to display division', $e);
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
            $division = $this->division->findOrFail($id);
            $division->name = $request->name;
            $division->description = $request->description;
            $division->parent_id = $request->induk_divisi;
            $division->save();
            $this->changeParentToTop($division);

            return $this->response('00', 'Division updated', $division);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to update division', $e);
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
            $division = $this->division->findOrFail($id);
            $this->changeParentToTop($division);
            $division->delete();

            return $this->response('00', 'Division deleted', $division);
        } catch (\Exception$e) {
            return $this->response('01', 'Failed to delete division', $e);
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

    public function changeParentToTop($division)
    {
        $parent = $division->parent;
        $childrens = $division->children;
        $parentCheck = $this->parentCheck($parent);

        $parentUpdate = null;
        if($parentCheck){
            $parentUpdate = $parent->id;
        }
        if ($childrens) {
            foreach ($childrens as $children) {
                $children->parent_id = $parentUpdate;
                $children->save();
            }
            $data = (object) [
                'parent' => $parent,
                'childrens' => $childrens,
            ];
            return $data;
        }
    }

    public function parentCheck($parent){
        if ($parent) {
            return true;
        }
        return false;
    }
}
