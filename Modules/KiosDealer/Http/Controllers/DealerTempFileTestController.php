<?php

namespace Modules\KiosDealer\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Actions\AwsFileUploaderAction;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Renderable;
use Modules\ReceivingGood\Rules\FileTypeRule;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Illuminate\Http\Exceptions\HttpResponseException;

class DealerTempFileTestController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    public function __construct(DealerFileTemp $dealer_file_temp)
    {
        $this->dealer_file_temp = $dealer_file_temp;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('kiosdealer::index');
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
        if (!$request->has("resources")) {
            $validator = Validator::make($request->all(), [
                "dealer_id" => "required",
                "file_type" => "required",
                "data" => "required_without:file|max:255",
                "file" => [
                    "file",
                    "mimetypes:image/jpeg,image/jpg,image/png",
                    new FileTypeRule(),
                ],
            ]);

            if ($validator->fails()) {
                return $this->response("04", "invalid data send", $validator->errors());
            }
        } else {
            $validator = Validator::make($request->all(), [
                "resources" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "invalid data send", $validator->errors());
            }

            if (count($request->resources[0]) < 3) {
                $data = [];
                $array_keys = array_keys($request->resources[0]);
                $request_list = [
                    "dealer_id" => "required",
                    "file_type" => "required",
                    "data" => "required",
                ];

                foreach ($request_list as $request) {
                    if (!in_array($request, $array_keys)) {
                        $data[$request] = ["validation.required"];
                    }
                }

                return $this->response("04", "invalid data send", $data);
            }
        }

        DB::beginTransaction();
        try {
            $dealer_file_temp = $this->dealer_file_temp;
            $data = $request->except("file");
            $response = [];

            /* batch store */
            if ($request->has("resources")) {
                foreach ($request->resources as $key => $value) {
                    $res = [];
                    foreach ($value as $attribute => $data) {
                        $res[$attribute] = $data;
                    }
                    $res = $dealer_file_temp->create($res);
                    array_push($response, $res);
                }
                return $this->response("00", "success", $response);
            }

            /* single store */
            foreach ($data as $key => $value) {
                $dealer_file_temp[$key] = $value;
            }

            $dealer_file_temp->save();

            if ($request->has("file")) {
                $path = (new AwsFileUploaderAction)($request->file("file"), $dealer_file_temp->id, "public/dealer/", "s3");

                if ($path["is_exist"]) {
                    $dealer_file_temp->data = $path["file_name"];
                    $dealer_file_temp->save();
                } else {
                    $dealer_file_temp->delete();
                    $response = $this->response("01", "failed", [
                        "message" => [
                            "file gagal diupload",
                        ],
                    ], 422);
                    throw new HttpResponseException($response);
                }
            }

            DB::commit();
            return $this->response("00", "success", $dealer_file_temp);
        } catch (\Throwable $th) {
            DB::rollback();
            $errors =  match (true) {
                app()->environment("production") => [
                    "message" => $th->message,
                    "request_id" => $request?->attributes->get('request_id'),
                ],
                default => [
                    "message" => $th->message,
                    "request_id" => $request?->attributes->get('request_id'),
                    "line" => $th->getLine(),
                    "file" => $th->getFile(),
                    "trace" => $th->getTrace(),
                ],
            };
            return $this->response("01", "failed", $errors);


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
        //
    }
}
