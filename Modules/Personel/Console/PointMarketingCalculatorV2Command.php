<?php

namespace Modules\Personel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\PointMarketing\ClassHelper\PointMarketingRule;
use Modules\Personel\Actions\Order\GetMarketingOrderYearAction;
use Modules\Personel\Actions\Point\RecalculateMarketingPointPerYearAction;
use Modules\Personel\Actions\Point\CalculateMarketingPointPerProductAction;

class PointMarketingCalculatorV2Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'point:recalculate_point_marketing_v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'recalculate all point marketing this year.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(
        CalculateMarketingPointPerProductAction $point_per_product_action,
        RecalculateMarketingPointPerYearAction $recalculate_point_action,
        GetMarketingOrderYearAction $marketing_order_year,
    ) {
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

        $nomor = 1;

        DB::beginTransaction();

        $personels = Personel::query()
            ->with([
                "point" => function ($QQQ) use ($current_year) {
                    return $QQQ->where("year", $current_year);
                },
            ])
            ->whereHas("salesOrder", function ($QQQ) use ($current_year) {
                return $QQQ
                    ->salesByYear($current_year)
                    ->consideredOrder();
            })
            ->orderBy("name")
            ->get()

        /* jrect marketing has redeem point */
            ->reject(function ($personel) {
                if ($personel->point) {
                    return $personel->point->status == "redeemed";
                }
            })

        /* reset marketing point first */
            ->each(function ($personel) use ($current_year) {

                PointMarketing::updateOrCreate(
                    [
                        "personel_id" => $personel->id,
                        "year" => $current_year,
                    ],
                    [
                        "marketing_point_total" => 0,
                        "marketing_point_active" => 0,
                        "marketing_point_adjustment" => 0,
                        "marketing_point_redeemable" => 0,
                    ]);
            })

            ->each(function ($personel) use (
                &$point_per_product_action,
                &$recalculate_point_action,
                &$marketing_order_year,
                $current_year,
                &$nomor,
            ) {

                $sales_orders = $marketing_order_year($personel->id, $current_year)

                /* order point rule */
                    ->filter(function ($order) {
                        return (new PointMarketingRule)->isConsideredOrderToGetPoint($order);
                    })

                    /* calculate point per product */
                    ->each(function ($order) use (&$point_per_product_action) {
                        $point_per_product_action($order);
                    });

                $marketing_point = $recalculate_point_action($personel->id, $current_year, $sales_orders);

                $current_point = [
                    "nomor" => $nomor,
                    "personel_id" => $personel->id,
                    "year" => $marketing_point?->year,
                    "personel_name" => $personel->name,
                    "status" => $marketing_point?->status,
                    "marketing_point_total" => $marketing_point?->marketing_point_total,
                    "marketing_point_active" => $marketing_point?->marketing_point_active,
                    "marketing_point_adjustment" => $marketing_point?->marketing_point_adjustment,
                    "marketing_point_redeemable" => $marketing_point?->marketing_point_redeemable,
                    "order_count" => $sales_orders->count(),
                ];

                dump($current_point);

                $nomor++;
            });

        DB::commit();

        /* telegram notifcation */
        telegram_notiication("Recalculate Point Marketing Done!");
    }
}
