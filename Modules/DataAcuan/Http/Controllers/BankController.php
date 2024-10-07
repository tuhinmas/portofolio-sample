<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Bank;
use Modules\DataAcuan\Entities\Country;

class BankController extends Controller
{
    public function __construct(Bank $bank, Country $country)
    {
        $this->bank = $bank;
        $this->country = $country;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $bank = $this->bank->with('countryOfBank')->get();
            $countries = $this->country->orderBy('label_en')->get();
            $data = (object) [
                'countries' => $countries,
                'banks' => $bank,
            ];

            return $this->response('00', 'bank displayed', $data);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data of bank', $e);
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
        $request->validate([
            'bank' => 'required|string|max:50',
            'kode_bank' => 'required|max:5',
            'country_id' => 'required',
        ]);

        try {
            $bank = $this->bank->firstOrCreate([
                'name' => $request->bank,
                'code' => $request->kode_bank,
                'country_id' => $request->country_id,
            ], [
                'IBAN' => $request->iban,
                'swift_code' => $request->swift_code,
            ]);
            $bank = $this->bank->query()
                ->where('id', $bank->id)
                ->with('countryOfBank')
                ->first();
            return $this->response('00', 'bank saved', $bank);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to save data of Bank', $e);
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
            $bank = $this->bank->findOrFail($id);
            return $this->response('00', 'bank show by id', $bank);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to show Bank by id', $e);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $bank = $this->bank->findOrFail($id);
            return $this->response('00', 'Bank edit by id', $bank);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to show Bank by id', $e);
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
        $request->validate([
            'bank' => 'required|string|max:50',
            'kode_bank' => 'required|max:5',
            'country_id' => 'required',
        ]);

        try {
            $bank = $this->bank->find($id);
            $bank->name = $request->bank;
            $bank->code = $request->kode_bank;
            $bank->country_id = $request->country_id;
            $bank->IBAN = $request->iban;
            $bank->swift_code = $request->swift_code;
            $bank->save();

            $bank = $this->bank->query()
                ->where('id', $bank->id)
                ->with('countryOfBank')
                ->first();
            return $this->response('00', 'bank updated', $bank);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to update Bank', $e);
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
            $bank = $this->bank->findOrFail($id);
            $bank->delete();
            return $this->response('00', 'Bank deleted', $bank);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to delete Bank', $e);
        }
    }

    /**
     * response message
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

    public function bank(Request $request)
    {
        try {
            $bank = $this->bank->query()
                ->with('countryOfBank')
                ->when($request->has("name"), function ($QQQ) {
                    return $QQQ->where("label_en", "like", "%" . $request->name . "%");
                });
                if ($request->has("limit")) {
                    $bank = $bank->paginate($request->limit ? $request->limit : 15);
                } else {
                    $bank = $bank->get();
                }
            return $this->response("00", "success", $bank);
        } catch (\Throwable$th) {
            return $this->respone("01", "failed, cannot get bank list", $th->getMessage(), 500);
        }
    }
}
