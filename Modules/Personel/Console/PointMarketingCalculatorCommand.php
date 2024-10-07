<?php

namespace Modules\Personel\Console;

use App\Traits\DistributorStock;
use Illuminate\Console\Command;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\PointMarketing\Entities\MarketingPointAdjustment;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class PointMarketingCalculatorCommand extends Command
{
    use PointMarketingTrait;
    use DistributorStock;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'point:recalculate_point_marketing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'recalculate all point marketing this year.';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogWorkerPointMarketingActive $log_worker_point_marketing_active,
        MarketingPointAdjustment $marketing_point_adjustment,
        LogWorkerPointMarketing $log_worker_point_marketing,
        LogWorkerSalesPoint $log_worker_sales_point,
        SalesOrderDetail $sales_order_detail,
        PointMarketing $point_marketing,
        SalesOrder $sales_order,
        Personel $personel

    ) {
        parent::__construct();
        $this->log_worker_point_marketing_active = $log_worker_point_marketing_active;
        $this->marketing_point_adjustment = $marketing_point_adjustment;
        $this->log_worker_point_marketing = $log_worker_point_marketing;
        $this->log_worker_sales_point = $log_worker_sales_point;
        $this->sales_order_detail = $sales_order_detail;
        $this->point_marketing = $point_marketing;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $current_year = now()->format("Y");

        if ($this->confirm('Recalculate for previous year?', false)) {
            $year = $this->choice(
                'which year?',
                [
                    now()->subYear(1)->format("Y"),
                    now()->subYear(2)->format("Y"),
                    now()->subYear(3)->format("Y"),
                ],
            );

            $current_year = $year;
        }

        $personels = $this->personel->query()
            ->whereHas("salesOrder", function ($QQQ) use ($current_year) {
                return $QQQ->pointMarketingByYear($current_year);

            })
            ->orWhereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->get();

        $nomor = 1;
        $personels->each(function ($personel) use ($current_year, &$nomor) {

            $point_marketing_per_product_recalculate = $this->recalcultePointMarketingPerProduct($personel->id, $current_year);
            $point_marketing = $this->recalcultePointMarketingTotal($personel->id, $current_year);
            $point_marketing_active = $this->recalcultePointMarketingActive($personel->id, $current_year);

            $current_point = [
                "nomor" => $nomor,
                "personel_id" => $personel->id,
                "personel_name" => $personel->name,
                "marketing_point_total" => $point_marketing?->marketing_point_total,
                "marketing_point_active" => $point_marketing?->marketing_point_active,
                "marketing_point_adjustment" => $point_marketing?->marketing_point_adjustment,
                "marketing_point_redeemable" => $point_marketing?->marketing_point_redeemable,
                "status" => $point_marketing?->status,
                "year" => $point_marketing?->year,
            ];
            $nomor++;
            dump($current_point);
        });
    }
}
