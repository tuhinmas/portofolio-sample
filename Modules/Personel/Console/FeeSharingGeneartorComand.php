<?php

namespace Modules\Personel\Console;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Actions\UpsertFeeTargetSharingAction;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class FeeSharingGeneartorComand extends Command
{
    use FeeMarketingTrait;
    use DistributorStock;
    use ChildrenList;

    protected $signature = 'fee:generate_fee_sharing {year?} {quarter?} {personelId?}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'fee:generate_fee_sharing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate fee sharing origin reguler and target';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogFeeTargetSharing $log_fee_target_sharing,
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
        $this->log_fee_target_sharing = $log_fee_target_sharing;
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
    public function handle(UpsertFeeTargetSharingAction $upsert_fee_target_action)
    {
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

            if ($this->confirm('Regenerate fee sharing for previous Quarter?', true)) {
                $quartal = $this->choice(
                    'which Quarter?',
                    [
                        1 => 1,
                        2 => 2,
                        3 => 3,
                        4 => 4,
                    ],
                );

                $quarter = $quartal;
            }

            $personelId = false;
            if ($this->confirm('Recalculate By Personel', false)) {
                $personelId = $this->ask('What is your Personel ID ?');
            }
        }

        $nomor = 1;

        $this->info("quarter: " . $quarter);

        $nomor = 1;

        DB::transaction(function () use (
            $upsert_fee_target_action,
            $current_year,
            $personelId,
            $console,
            $quarter,
        ) {
            $personels = $this->personel->query()
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", marketing_positions());
                })
                ->when($personelId, function ($QQQ) use ($personelId) {
                    return $QQQ->where("id", $personelId);
                })
                ->when(!$personelId, function ($QQQ) {
                    return $QQQ->where(function ($QQQ) {
                        return $QQQ
                            ->whereHas("salesOrder")
                            ->orWhereHas("marketingFee");
                    });
                })
                ->orderBy("name")
                ->get()
                ->each(function ($personel) use (
                    $current_year,
                    $quarter,
                    &$nomor,
                    &$console,
                ) {

                    /* recalculate fee per product */
                    /* fee sharring origin generator */
                    /* fee sharing calculator */
                    $sales_orders = $this->sales_order->query()
                        ->with([
                            "subDealer",
                            "dealer" => function ($QQQ) {
                                return $QQQ->with([
                                    "ditributorContract",
                                ]);
                            },
                            "salesOrderDetail" => function ($QQQ) {
                                return $QQQ->with([
                                    "allSalesOrderOrigin",
                                ]);
                            },
                            "salesCounter",
                            "statusFee",
                            "invoice",
                        ])
                        ->feeMarketing($current_year, $quarter)
                        ->where("personel_id", $personel?->id)
                        ->get()
                        ->sortBy("order_number")
                        ->each(function ($order) use (&$console) {

                            $this->fee_sharing_origin->query()
                                ->where("sales_order_id", $order->id)
                                ->get()
                                ->each(function ($origin) {
                                    $origin->forceDelete();
                                });

                            $this->fee_target_sharing_origin->query()
                                ->where("sales_order_id", $order->id)
                                ->get()
                                ->each(function ($origin) {
                                    $origin->forceDelete();
                                });

                            $active_contract = $this->distributorActiveContract($order->store_id, confirmation_time($order)->format("Y-m-d"));
                            if ($active_contract) {
                                $this->info("<fg=red>Sales to distributor active</>");
                                return true;
                            }

                            /* recalculate point per product */
                            $this->feeMarketingPerProductCalculator($order);

                            if ($console) {

                                /* fee sharing generator */
                                dump($this->feeSharingOriginGenerator($order) . " " . $order->order_number);

                                /* fee target sharing generator */
                                dump($this->feeTargetSharingOriginGenerator($order));
                            } else {
                                $this->feeSharingOriginGenerator($order);
                                $this->feeTargetSharingOriginGenerator($order);
                            }
                        });

                });

            $personels = $this->personel->query()
                ->whereHas("feeTargetSharingSoOrigins", function ($QQQ) use ($current_year, $quarter) {
                    return $QQQ
                        ->whereYear("confirmed_at", $current_year)
                        ->whereRaw("quarter(confirmed_at) = ?", $quarter);
                })
                ->each(function ($personel) use ($upsert_fee_target_action, $current_year, $quarter) {

                    dump($personel->id);

                    /* fee target sharing */
                    dump($this->feeTargetSharingSpvGenerator($upsert_fee_target_action, $personel->id, $current_year, $quarter)->count());
                });
        });

    }
}
