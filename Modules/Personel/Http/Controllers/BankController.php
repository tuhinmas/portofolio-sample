<?php

namespace Modules\Personel\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelBank;
use Modules\Personel\Http\Requests\BankPersonelRequest;

class BankController extends Controller
{
    public function __construct(PersonelBank $personel_bank, Personel $personel)
    {
        $this->personel_bank = $personel_bank;
        $this->personel = $personel;
    }

    /**
     * Display a listing of the resource.
     * @param int $id = personel_id
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $banks = $this->personel_bank->query()
                ->where('personel_id', $request->personel_id)
                ->with('bank')
                ->get();
            return $this->response('00', 'Personel bank index', $banks);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display personel bank index', $th->getMessage());
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
     * @param int $id = personel_id
     */
    public function store(BankPersonelRequest $request)
    {
        try {
            $bank = $this->personel_bank->firstOrCreate([
                'rek_number' => $request->rekening,
            ], [
                'owner' => $request->pemilik,
                'personel_id' => $request->personel_id,
                'bank_id' => $request->bank_id,
                'branch' => $request->cabang,
                'swift_code' => $request->swift_code,
            ]);
            return $this->response('00', 'Personel bank saved', $bank);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display personel bank index', $th->getMessage());
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
     * @param int $id = bank_personels_id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $bank = $this->personel_bank->find($id);
            return $this->response('00', ' personel bank edit', $bank);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display bank edit', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id = bank_personels_id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $bank = $this->personel_bank->findOrFail($id);
            $bank->owner = $request->pemilik;
            $bank->bank_id = $request->bank_id;
            $bank->branch = $request->cabang;
            $bank->rek_number = $request->rekening;
            $bank->swift_code = $request->swift_code;
            $bank->save();
            return $this->response('00', 'Personel bank updated', $bank);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update personel bank', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Request $request, $id)
    {
        try {
            if ($request->personel_id && $request->bank_id) {
                $personel = $this->personel->findOrFail($request->personel_id);
                $bank = [$request->bank_id];
                $personel_bank = $personel->bankPersonel()
                    ->wherePivot('id', $id)
                    ->detach($bank);
                return $this->response('00', 'Personel bank deleted', $personel);
            } else {
                $personel_bank = $this->personel_bank->findOrFail($id);
                $personel_bank->delete();
                $personel = $this->personel->findOrFail($request->personel_id);
                return $this->response("00", "success, personel bank deleted", $personel_bank);
            }

        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to delete personel bank', $th->getMessage());
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
