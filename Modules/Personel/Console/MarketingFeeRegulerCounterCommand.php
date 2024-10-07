<?php

namespace Modules\Personel\Console;

use App\Traits\ChildrenList;
use App\Traits\ResponseHandler;
use Illuminate\Console\Command;
use App\Traits\DistributorStock;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\Personel\Actions\GetFeeTargetTotalPerQuarterAction;
use Modules\Personel\Actions\GetFeeTargetTotalActivePerQuarterAction;
use Modules\Personel\Actions\GetFeeTargetTotalActivePendingPerQuarterAction;

class MarketingFeeRegulerCounterCommand extends Command
{
    use FeeMarketingTrait;
    use DistributorStock;
    use ResponseHandler;
    use ChildrenList;

    protected $signature = 'fee:marketing_fee_reguler_counter {year?} {quarter?} {personelId?}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'fee:marketing_fee_reguler_counter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reclaculate and generate all marketing fee.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        FeeTargetSharing $fee_target_sharing,
        MarketingFee $marketing_fee,
        FeePosition $fee_position,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->fee_target_sharing = $fee_target_sharing;
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(
        GetFeeTargetTotalPerQuarterAction $fee_target_total_action,
        GetFeeTargetTotalActivePerQuarterAction $fee_target_total_active_action,
        GetFeeTargetTotalActivePendingPerQuarterAction $fee_target_total_active_pending_action,
    ) {
        $current_year = now()->format("Y");
        $quarter = now()->quarter;
        $console = true;
        $personelId = false;

        if (!empty($this->argument('year')) ||
            !empty($this->argument('quarter')) ||
            !empty($this->argument('personelId'))
        ) {
            $current_year = $this->argument('year') ?? $current_year;
            $quarter = $this->argument('quarter') ?? $quarter;
            $personelId = $this->argument('personelId') ?? $personelId;
            $console = false;
        } else {
            if ($this->confirm('Regenerate fee sharing for previous year?', false)) {
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

            if (!$this->confirm('Recalculate fee for current quarter?', false)) {
                $quarter = $this->anticipate('which quarter? (1-4)', [1, 2, 3, 4]);
            }

            $personelId = false;
            if ($this->confirm('Recalculate By Personel', false)) {
                $personelId = $this->ask('What is your Personel ID ?');
            }
        }

        $this->info("quarter: " . $quarter);

        $fee_product_reference = DB::table('fee_products')
            ->where("year", $current_year)
            ->where("quartal", $quarter)
            ->where("type", "1")
            ->get()
            ->pluck("product_id");

        $fee_target_product_reference = DB::table('fee_products')
            ->where("year", $current_year)
            ->where("type", "2")
            ->where("quartal", $quarter)
            ->get()
            ->pluck("product_id")
            ->unique()
            ->values();

        $nomor = 1;
        $personels = $this->personel->query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->when($personelId, function ($QQQ) use ($personelId) {
                return $QQQ->where("id", $personelId);
            })
            ->when(!$personelId, function ($QQQ) {
                return $QQQ
                    ->whereHas("salesOrder")
                    ->orWhereHas("marketingFee");
            })
            ->orderBy("name")
            ->get()
            ->each(function ($personel) use (
                $fee_target_total_active_pending_action,
                $fee_target_total_active_action,
                $fee_target_product_reference,
                $fee_target_total_action,
                $fee_product_reference,
                $current_year,
                $quarter,
                $console,
                &$nomor,
            ) {

                /* recalculate fee per product */
                /* fee sharring origin generator */
                /* fee sharing calculator */
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        },
                        "salesOrderDetail",
                        "salesCounter",
                        "statusFee",
                        "invoice",
                    ])
                    ->quartalOrder($current_year, $quarter)
                    ->where("personel_id", $personel?->id)
                    ->get()
                    ->each(function ($order) {

                        /* recalculte fee in origin */
                        $this->feeSharingOriginCalculator($order);
                    });

                /* fee target sharing to supervisor */

                for ($i = 1; $i < 5; $i++) {
                    $this->marketing_fee->firstOrCreate([
                        "personel_id" => $personel->id,
                        "year" => $current_year,
                        "quarter" => $i,
                    ], [
                        "fee_reguler_total" => 0,
                        "fee_reguler_settle" => 0,
                        "fee_target_total" => 0,
                        "fee_target_settle" => 0,
                    ]);
                }

                /* fee reguler */
                // $marketing_fee_total = $this->feeMarketingRegulerTotal($personel->id, $current_year, $quarter);
                // $marketing_fee_active = $this->feeMarketingRegulerActive($personel->id, $current_year, $quarter);
                // $marketing_fee_active_pending = $this->feeMarketingRegulerActive($personel->id, $current_year, $quarter, null, "pending");

                // dd($marketing_fee_total);

                /* fe target */
                // $marketing_fee_target_total = $this->feeMarketingTargetTotal($personel->id, $current_year, $quarter);
                // $marketing_fee_target_active = $this->feeMarketingTargetActive($personel->id, $current_year, $quarter);
                // $marketing_fee_target_active_pending = $this->feeMarketingTargetActive($personel->id, $current_year, $quarter, "pending");

                // $payload = [
                //     "year" => $current_year, 
                //     "quarter" => $quarter, 
                //     "personel_id" => $personel->id
                // ];

                // $marketing_fee_target_total = $fee_target_total_action($payload);
                // $marketing_fee_target_active = $fee_target_total_active_action($payload);
                // $marketing_fee_target_active_pending = $fee_target_total_active_pending_action($payload);

                // $marketing_fee = $this->marketing_fee->query()
                //     ->where("personel_id", $personel->id)
                //     ->where("year", $current_year)
                //     ->where("quarter", $quarter)
                //     ->first();

                // $old_fee = [
                //     "personel_id" => $personel->id,
                //     "year" => $current_year,
                //     "quarter" => $quarter,
                //     "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                //     "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                //     "fee_target_total" => $marketing_fee->fee_target_total,
                //     "fee_target_settle" => $marketing_fee->fee_target_settle,
                // ];

                // $marketing_fee->fee_reguler_total = $marketing_fee_total;
                // $marketing_fee->fee_reguler_settle = $marketing_fee_active;
                // $marketing_fee->fee_reguler_settle_pending = $marketing_fee_active_pending;
                // $marketing_fee->fee_target_total = $marketing_fee_target_total;
                // $marketing_fee->fee_target_settle = $marketing_fee_target_active;
                // $marketing_fee->fee_target_settle_pending = $marketing_fee_target_active_pending;
                // $marketing_fee->save();

                // if ($console) {
                //     dump([
                //         "nomor" => $nomor,
                //         "personel_id" => $personel->id,
                //         "personel_name" => $personel->name,
                //         "year" => $current_year,
                //         "quarter" => $quarter,
                //         "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                //         "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                //         "fee_reguler_settle_pending" => $marketing_fee->fee_reguler_settle_pending,
                //         "fee_target_total" => $marketing_fee->fee_target_total,
                //         "fee_target_settle" => $marketing_fee->fee_target_settle,
                //         "fee_target_settle_pending" => $marketing_fee->fee_target_settle_pending,
                //     ]);
                // }
                
                // $nomor++;

                // $test = activity()
                //     ->causedBy(auth()->id())
                //     ->performedOn($marketing_fee)
                //     ->withProperties([
                //         "old" => $old_fee,
                //         "attributes" => $marketing_fee,
                //     ])
                //     ->tap(function (Activity $activity) {
                //         $activity->log_name = 'sync';
                //     })
                //     ->log('marketing point syncronize');
            });
    }
}
