<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\DistributorStock;
use App\Traits\ResponseHandler;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\PointMarketing\Entities\MarketingPointAdjustment;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class RecalculatePointMarketingController extends Controller
{
    use PointMarketingTrait;
    use AuthorizesRequests;
    use DistributorStock;
    use ResponseHandler;

    public function __construct(
        LogWorkerPointMarketingActive $log_worker_point_marketing_active,
        MarketingPointAdjustment $marketing_point_adjustment,
        LogWorkerPointMarketing $log_worker_point_marketing,
        LogWorkerSalesPoint $log_worker_sales_point,
        SalesOrderDetail $sales_order_detail,
        PointMarketing $point_marketing,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->log_worker_point_marketing_active = $log_worker_point_marketing_active;
        $this->log_worker_point_marketing = $log_worker_point_marketing;
        $this->marketing_point_adjustment = $marketing_point_adjustment;
        $this->log_worker_sales_point = $log_worker_sales_point;
        $this->sales_order_detail = $sales_order_detail;
        $this->point_marketing = $point_marketing;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    public function __invoke(Request $request, $personel_id)
    {

        /**
         * only autrhorized muser can syn point marketing
         */
        $this->authorize("syncPointMarketing", $this->point_marketing);

        $personel = $this->personel->findOrFail($personel_id);

        $request->validate([
            "year" => [
                "required",
                "digits:4",
                "integer",
                "min:2000",
                "max:" . (date('Y')),
            ],
        ], [
            "year.max" => "max year is " . date('Y'),
        ]);

        try {

            /**
             * update marketing point per product
             */
            if ($request->update_point_per_product) {
                $point_per_product = $this->recalcultePointMarketingPerProduct($personel_id, $request->year);
            }

            /**
             * calculate point marketing total
             */
            $point_total = $this->recalcultePointMarketingTotal($personel_id, $request->year);

            /**
             * calculate point marketing active
             */
            $point_marketing_active = $this->recalcultePointMarketingActive($personel_id, $request->year);

            return $this->response("00", "success", $point_marketing_active);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "message" => $th->getMessage(),
                "file" => $th->getFile(),
                "line" => $th->getLine(),
            ]);
        }
    }
}
