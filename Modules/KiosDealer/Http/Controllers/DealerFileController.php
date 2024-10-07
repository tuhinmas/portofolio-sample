<?php

namespace Modules\KiosDealer\Http\Controllers;

use File;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\KiosDealer\Entities\DealerFile;
use Modules\KiosDealer\Http\Requests\DealerFileRequest;
use Illuminate\Support\Facades\Storage;

class DealerFileController extends Controller
{
    public function __construct(DealerFile $dealer_file, DealerLogController $log)
    {
        $this->dealer_file = $dealer_file;
        $this->log = $log;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $dealer_files = $this->dealer_file->query()
                ->where('dealer_id', $request->dealer_id)
                ->orderBy('created_at')
                ->get();

            return $this->response('00', 'dealer file index', $dealer_files);
        } catch (\Throwable$th) {
            return $this->response('00', 'failed to display dealer files', $th);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('kiosdealer::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $dealer_file = $this->dealer_file->firstOrCreate([
                'dealer_id' => $request->dealer_id,
                'file_type' => $request->attachment_name,
                'data' => $request->file_name
            ]);
            // $data = $this->attachment($request, $dealer_file);
            $this->dealerLog($dealer_file->dealer_id, "add attachment");
            return $this->response('00', 'Dealer file saved', $dealer_file);
        } catch (\Throwable $th) {
            return $this->response('01', 'faied to save dealer file', $th);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('kiosdealer::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id = dealer file id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $dealer_file = $this->dealer_file->findOrFail($id);
            return $this->response('00','dealer file edit', $dealer_file);
        } catch (\Throwable $th) {
            return $this->response('01','failed to display dealer file edit', $th);
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
            $dealer_file = $this->dealer_file->findOrFail($id);
            $dealer_file->file_type = $request->attachment_name;
            $dealer_file->data = $request->file_name;
            $dealer_file->save();

            $this->dealerLog($dealer_file->dealer_id, "update attachment");
            
            return $this->response('00','dealer file updated', $dealer_file);
        } catch (\Throwable $th) {
            return $this->response('01','failed to update dealer file', $th);
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
            $dealer_file = $this->dealer_file->findOrFail($id);
            $dealer_file->delete();
            $this->dealerLog($dealer_file->dealer_id, "delete attachment");

            return $this->response('00','dealer file deleted', $dealer_file);
        } catch (\Exception $th) {
            return $this->response('01','failed to delete dealer file', $th);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $request
     * @param [type] $attachment = attachment_name
     * @return void
     */
    public function attachment($request, $dealer_file)
    {
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $attachment_extension = $attachment->getClientOriginalExtension();
            $attachment_name = $dealer_file->id . '-' . $request->attachment_name . '.' . $attachment_extension;
            $attachment_folder = '/public/dealerFile';
            $attachment_location = $attachment_folder . $attachment_name;
            try {
                // $attachment->move(public_path($attachment_folder), $attachment_name);
                $path = $request->file('attachment')->storeAs('/public/dealerFile', $attachment_name);
                $dealer_file->data = $path;
                // $dealer_file->data = $attachment_location;
                $dealer_file->save();
            } catch (\Exception $e) {
                return response()->json([
                    'response_code' => '01',
                    'response_msg' => 'lampiran gagal di tambahkan',
                    'data' => $e,
                ], 500);
            }
        }
        else {
            return response()->json([
                'response_code' => '01',
                'response_msg' => 'Imgae cannot be null',
            ], 500);  
        }
        return $dealer_file;
    }

    /**
     * remove file
     *
     * @param [type] $path
     * @return void
     */
    public function remove_file($path)
    {
        try {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'response_code' => '01',
                'response_msg' => 'lampiran gagal di hapus',
                'data' => $th
            ], 500);
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

      /**
     * dealer log
     *
     * @param [type] $dealer_id
     * @param [type] $activity (crud)
     * @return void
     */
    public function dealerLog($dealer_id, $activity){
        $request = new Request;
        $request["dealer_id"] = $dealer_id;
        $request["activity"] = $activity;
        $this->log->store($request);
    }

    public function deleteDealerFileById($id){
        try {
            $dealer_file = $this->dealer_file->where("dealer_id", $id)->delete();
            $this->dealerLog($id, "delete file update");
            return $this->response('00','dealer file deleted', $dealer_file);
        } catch (\Exception $th) {
            return $this->response('01','failed to delete dealer file', $th->getMessage());
        }
    }
}
