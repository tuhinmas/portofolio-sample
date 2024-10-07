<?php

namespace Modules\Organisation\Http\Controllers;

use App\Models\Contact;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrganisationContactController extends Controller
{
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $contact = $this->contact->query()
                ->where('parent_id', $request->organisation_id)
                ->orderBy('contact_type')
                ->get();
            return $this->response('00', 'Organisation index', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display organisation index', $th->getMessage());
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
            $contact = $this->contact->create([
                'contact_type' => $request->contact_type,
                'data' => $request->contact_detail,
                'parent_id' => $request->organisation_id,
            ]);
            return $this->response('00', 'Organisation contact saved', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to save organisation contact', $th->getMessage());
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
     * @param int $id = contact_id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $contact = $this->contact->findOrFail($id);
            return $this->response('00', 'Organisation edit', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display organisation contact detail', $th->getMessage());
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
            $contact = $this->contact->findOrFail($id);
            $contact->contact_type = $request->contact_type;
            $contact->data = $request->contact_detail;
            $contact->save();
            return $this->response('00', 'Organisation contact updated', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update organisation contact', $th->getMessage());
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
            $contact = $this->contact->findOrFail($id);
            $contact->delete();
            return $this->response('00', 'Organisation contact deleted', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to delete organisation contact', $th->getMessage());
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
