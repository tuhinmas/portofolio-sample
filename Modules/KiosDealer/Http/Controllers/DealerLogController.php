<?php

namespace Modules\KiosDealer\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\KiosDealer\Entities\DealerLog;
use Illuminate\Contracts\Support\Renderable;

class DealerLogController extends Controller
{
    public function __construct(DealerLog $log){
        $this->log = $log;
        $this->user = auth()->id();
    }
    use ResponseHandler;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $logs = $this->log->orderBy('created_at')->ger();
            return $this->response('00', 'dealer logs index', $logs);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get dealer logs index', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
       return "ok";
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $user = $this->user;;
            $log = $this->log->create([
                "user_id" => $user,
                "dealer_id" => $request->dealer_id,
                "activity" => $request->activity
            ]);

            return $this->response('00','dealer log saved', $log);
        } catch (\Throwable $th) {
            return $this->response('01','failed to save dealer log', $th->getMessage());
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
            $log = $this->log->findOrFail($id);
            return $this->response('00','dealer log saved', $log);
        } catch (\Throwable $th) {
            return $this->response('01','failed to save dealer log', $th->getMessage());
        }    
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('kiosdealer::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $log = $this->log->findOrFail($id);
            $log->delete();
            return $this->response('00','dealer log saved', $log);
        } catch (\Throwable $th) {
            return $this->response('01','failed to save dealer log', $th->getMessage());
        }   
    }
}
