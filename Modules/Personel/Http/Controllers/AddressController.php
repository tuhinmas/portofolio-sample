<?php

namespace Modules\Personel\Http\Controllers;

use App\Models\Address;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;

class AddressController extends Controller
{
    public function __construct(Address $address, Personel $personel)
    {
        $this->address = $address;
        $this->personel = $personel;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $address = $this->personel->query()
                ->with('address')
                ->where('id', $request->personel_id)
                ->get();

            return $this->response('00', 'personel address index', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display personel address', $th->getMessage());
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
            $address = $this->address->create([
                'gmaps_link' => $request->gmaps_link,
                'parent_id' => $request->personel_id,
                'type' => $request->address_type,
                'country_id' => $request->country_id,
                'detail_address' => $request->address,
                "province_id" => $request->province_id,
                "city_id" => $request->city_id,
                "district_id" => $request->district_id,
                "post_code" => $request->post_code
            ]);
            return $this->response('00', 'personel addres saved', $address);
        } catch (\Exception$th) {
            return $this->response('01', 'failed to save apersonel address', $th->getMessage());
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
     * @param int $id = address_id
     * @return Renderable
     */
    public function edit($id)
    {
        $address = $this->address->find($id);
        return response()->json([
            'response_code' => "00",
            'response_message' => 'personel address edit',
            "data" => $address
        ]);

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
            return $this->response('00', ' Personel address updated', $address);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update personel address', $th->getMessage());
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
            $address = $this->address->find($id);
            $address->delete();
            return $this->response('00', 'personel address deleted', $address);
        } catch (\Throwable$th) {
            return $this->response('00', 'failed to delete personel address', $th->getMessage());
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
