<?php

namespace Modules\PickupOrder\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\PickupOrder\Entities\PickupOrderFile;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;
use Modules\PickupOrder\Actions\PickupFileUploaderAction;
use Modules\PickupOrder\Rules\PickupOrderDetailFileTypeRule;

class PickupOrderFileUploaderController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected PickupOrderFile $pickup_order_file,
        protected PickupOrderDetail $pickup_order_detail,
        protected PickupOrderDetailFile $pickup_order_detail_file,
    ) {}

    /**
     * upload file for pickup order detail
     *
     * @param Request $request
     * @param PickupFileUploaderAction $file_uploader
     * @param [type] $pickup_order_detail_id
     * @return void
     */
    public function pickupDetailFileUpload(Request $request, PickupFileUploaderAction $file_uploader, $pickup_order_detail_id)
    {
        $request->validate([
            "file" => "required",
            "type" => [
                "required",
                new PickupOrderDetailFileTypeRule($pickup_order_detail_id),
            ],
        ]);

        $this->pickup_order_detail->findOrFail($pickup_order_detail_id);
        try {
            $env = app()->environment();
            $base_path = "public/pickup-order/pickup-order-detail/file/$env";
            $path = $file_uploader($request->file("file"), $pickup_order_detail_id, $base_path, "s3");

            $pickup_order_detail_file = $this->pickup_order_detail_file->create([
                "pickup_order_detail_id" => $pickup_order_detail_id,
                "attachment" => $path,
                "type" => $request->type,
            ]);

            return $this->response("00", "succes", $pickup_order_detail_file);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }

    /**
     * upload file for pickup order
     *
     * @param Request $request
     * @param PickupFileUploaderAction $file_uploader
     * @param [type] $pickup_order_id
     * @return void
     */
    public function pickupFileUpload(Request $request, PickupFileUploaderAction $file_uploader, $pickup_order_id)
    {
        $request->validate([
            "file" => "required",
            "caption" => "required",
        ]);

        try {
            $env = app()->environment();
            $base_path = "public/pickup-order/file/$env";
            $path = $file_uploader($request->file("file"), $pickup_order_id, $base_path, "s3");

            $pickup_order_file = $this->pickup_order_file->create([
                "pickup_order_id"=> $pickup_order_id,
                "caption" => $request->caption,
                "attachment" => $path
            ]);
            return $this->response("00", "succes", $pickup_order_file);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
