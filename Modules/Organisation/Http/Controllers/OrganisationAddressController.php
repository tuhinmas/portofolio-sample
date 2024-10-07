<?php

namespace Modules\Organisation\Http\Controllers;

use App\Models\Address;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrganisationAddressController extends Controller
{
    public function __construct(Address $address)
    {
        $this->address = $address;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $address = $this->address->query()
                ->where('parent_id', $request->organisation_id)
                ->with("province", "city", "district")
                ->get();
            return $this->response('00', 'Organisation address index', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed display organisation index', $th->getMessage());
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
            $address = $this->address->create([
                'gmaps_link' => $request->gmaps_link,
                'parent_id' => $request->organisation_id,
                'type' => $request->address_type,
                'country_id' => $request->country_id,
                'detail_address' => $request->address,
                "province_id" => $request->province_id,
                "city_id" => $request->city_id,
                "district_id" => $request->district_id,
                "post_code" => $request->post_code,
            ]);

            return $this->response('00', 'Organisation address saved', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to save organisation address', $th->getMessage());
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
            $address = $this->address->findOrFail($id);
            return $this->response('00', 'Organisation address edit', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display organisation edit', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id = adres_id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $address = $this->address->findOrFail($id);
            $address = $this->address->where("id", $id)->with("province", "city", "district")->first();
            return $this->response('00', 'Organisation address edit', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display organisation edit', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = address id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $address = $this->address->find($id);
            $address->gmaps_link = $request->gmaps_link;
            $address->type = $request->address_type;
            $address->detail_address = $request->address;
            $address->country_id = $request->country_id;
            $address->province_id = $request->province_id;
            $address->city_id = $request->city_id;
            $address->district_id = $request->district_id;
            $address->post_code = $request->post_code;
            $address->save();
            return $this->response('00', 'Organisation address updated', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update organisation address', $th->getMessage());
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
            $address = $this->address->findOrFail($id);
            $address->delete();
            return $this->response('00', 'Organisation address deleted', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to delete organisation address', $th->getMessage());
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
