<?php

namespace Modules\Personel\Http\Controllers;

use App\Models\Contact;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;

class ContactController extends Controller
{
    public function __construct(Contact $contact, Personel $personel)
    {
        $this->contact = $contact;
        $this->personel = $personel;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     * @param $id = personel_id
     */
    public function index(Request $request)
    {
        try {
            $contact = $this->contact->query()
                ->where('parent_id', $request->personel_id)
                ->orderBy('contact_type')
                ->get();
            return $this->response('00', 'personel contact index', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display personel index', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('personel::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $contact = $this->contact->firstOrCreate([
                'contact_type' => $request->contact_type,
                'data' => $request->contact_detail,
            ], [
                'parent_id' => $request->personel_id,
            ]);

            return $this->response('00', 'personel contact saved', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save personel contact', $th->getMessage());
        }

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('personel::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id = contact_id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $contact = $this->contact->findorFail($id);
            return $this->response('00', 'Personel contact edit', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display personel contact', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = contact_id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $contact = $this->contact->findorFail($id);
            $contact->contact_type = $request->contact_type;
            $contact->data = $request->contact_detail;
            $contact->save();
            return $this->response('00', 'personel contact updated', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update personel contact', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id = contact_id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $contact = $this->contact->findorFail($id);
            $contact->delete();
            return $this->response('00', 'personel contact deleted', $contact);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete personel contact', $th->gteMessage());
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
