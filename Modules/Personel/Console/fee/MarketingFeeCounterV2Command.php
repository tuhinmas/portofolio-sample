<?php

namespace Modules\Personel\Console\fee;

use Illuminate\Console\Command;
use App\Traits\DistributorStock;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\Personel\Actions\GetMarketingAndSupervisorAction;
use Modules\Personel\Actions\Marketing\UpsertMarketingFeeAction;
use Modules\Distributor\Actions\GetDistributorActiveContractAction;
use Modules\SalesOrderV2\Actions\CalculateMarketingFeeByOrderAction;
use Modules\SalesOrderV2\Actions\GenerateFeeTargetSharingOriginAction;
use Modules\SalesOrderV2\Actions\CalculateFeeMarketingPerProductAction;
use Modules\SalesOrderV2\Actions\GenerateFeeRegulerSharingOriginAction;
use Modules\SalesOrderV2\Actions\GenerateFeeTargetNominalSharingAction;
use Modules\SalesOrderV2\Actions\CalculateFeeRegulerSharingOriginAction;
use Modules\SalesOrderV2\Actions\CalculateMarketingFeeByMarketingAction;
use Modules\SalesOrderV2\Actions\GetFeeProductRegulerReferenceByQuarterAction;

class MarketingFeeCounterV2Command extends Command
{
    use DistributorStock;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'fee:marketing_fee_counter_v2';

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
        FeeSharingSoOrigin $fee_sharing_origin,
        FeeTargetSharing $fee_target_sharing,
        MarketingFee $marketing_fee,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->fee_target_sharing = $fee_target_sharing;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
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
        GenerateFeeTargetNominalSharingAction $generate_fee_target_nominal_sharing_action,
        GetFeeProductRegulerReferenceByQuarterAction $fee_reguler_reference_action,
        GenerateFeeRegulerSharingOriginAction $generate_fee_reguler_sharing_action,
        GenerateFeeTargetSharingOriginAction $generate_fee_target_sharing_action,

        CalculateFeeRegulerSharingOriginAction $calculate_fee_reguler_sharing_action,
        CalculateMarketingFeeByOrderAction $calculate_marketing_fee_by_order_action,
        CalculateMarketingFeeByMarketingAction $marketing_fee_per_marketing_action,
        CalculateFeeMarketingPerProductAction $fee_per_product_action,

        GetDistributorActiveContractAction $distributor_contract,
        GetMarketingAndSupervisorAction $marketing_supervisor,
        UpsertMarketingFeeAction $marketig_fee_action,

    ) {
        $year = now()->format("Y");
        $quarter = now()->quarter;
        $personel_id = false;
        $generate_fee_sharing = false;

        if ($this->confirm('Regenerate fee sharing for previous year?', false)) {
            $year = $this->choice(
                'which year?',
                [
                    now()->subYear(1)->format("Y"),
                    now()->subYear(2)->format("Y"),
                    now()->subYear(3)->format("Y"),
                ],
            );
        }

        if (!$this->confirm('Recalculate fee for current quarter?', false)) {
            $quarter = $this->anticipate('which quarter? (1-4)', [1, 2, 3, 4]);
        }

        $personel_id = false;
        if ($this->confirm('Recalculate By Personel', false)) {
            $personel_id = $this->ask('What is your Personel ID ?');
        }

        if ($this->confirm('Generate fee sharing?', false)) {
            $generate_fee_sharing = true;
        }

        $this->info("quarter: " . $quarter);

        try {
            DB::beginTransaction();
            $nomor = 1;

            $personels = $this->personel->query()
                ->with([
                    "feeTargetSharingSoOrigins" => function ($QQQ) use ($year, $quarter) {
                        return $QQQ
                            ->whereYear("confirmed_at", $year)
                            ->whereRaw("quarter(confirmed_at) = ?", $quarter);
                    },
                ])
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", marketing_positions());
                })
                ->when($personel_id, function ($QQQ) use ($personel_id) {
                    return $QQQ->where("id", $personel_id);
                })
                ->when(!$personel_id, function ($QQQ) {
                    return $QQQ
                        ->orWhere(function ($QQQ) {
                            return $QQQ
                                ->whereHas("salesOrder")
                                ->orWhereHas("marketingFee");
                        });
                })
                ->orderBy("name")
                ->get()

            /* generate fee sharing */
                ->when($generate_fee_sharing, function ($personels) use (
                    $calculate_marketing_fee_by_order_action,
                    $calculate_fee_reguler_sharing_action,
                    $generate_fee_reguler_sharing_action,
                    $generate_fee_target_sharing_action,
                    $fee_reguler_reference_action,
                    $fee_per_product_action,
                    $distributor_contract,
                    $generate_fee_sharing,
                    $marketig_fee_action,
                    $personel_id,
                    $quarter,
                    &$nomor,
                    $year,
                ) {
                    return $personels
                        ->each(function ($personel) use (
                            $calculate_marketing_fee_by_order_action,
                            $calculate_fee_reguler_sharing_action,
                            $generate_fee_reguler_sharing_action,
                            $generate_fee_target_sharing_action,
                            $fee_reguler_reference_action,
                            $fee_per_product_action,
                            $distributor_contract,
                            $generate_fee_sharing,
                            $marketig_fee_action,
                            $personel_id,
                            $quarter,
                            &$nomor,
                            $year,
                        ) {

                            /* recalculate fee per product */
                            /* fee sharring origin generator */
                            /* fee sharing calculator */
                            $this->sales_order->query()
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
                                    "personel",
                                ])
                                ->consideredMarketingSalesByQuarter($personel->id, $year, $quarter)
                                ->get()
                                ->sortBy("order_number")
                                ->each(function ($order) use (
                                    $calculate_marketing_fee_by_order_action,
                                    $calculate_fee_reguler_sharing_action,
                                    $generate_fee_reguler_sharing_action,
                                    $generate_fee_target_sharing_action,
                                    $fee_reguler_reference_action,
                                    $fee_per_product_action,
                                    $distributor_contract,
                                    $personel_id,
                                    $personel,
                                    $quarter,
                                    &$nomor,
                                    $year,
                                ) {

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

                                    $active_contract = $distributor_contract($order->store_id, confirmation_time($order)->format("Y-m-d"));

                                    /* recalculate point per product */
                                    $fee_per_product_action($fee_reguler_reference_action, $order, $active_contract);
                                    $generate_fee_reguler_sharing_action($order, $active_contract);
                                    $calculate_fee_reguler_sharing_action($fee_reguler_reference_action, $order);
                                    // $fee = $calculate_marketing_fee_by_order_action($order);

                                    /* fee target */
                                    $fee_target = $generate_fee_target_sharing_action($order, $active_contract);

                                    dump($fee_target);

                                    dump([
                                        "nomor" => $nomor,
                                        "type" => $order->type == "2" ? "indirect" : "direct",
                                        "order_number" => $order->order_number,
                                        "marketing" => $order->personel->name,
                                        ($order->type == "2" ? "nota" : "proforma") => $order->type == "2" ? $order->date : $order->invoice->invoice,
                                        // "fee" => $fee->toArray(),
                                        "considered_to_get_fee" => ($order->is_office ? "as office" : ($active_contract ? "Sales to distributor active" : true)),
                                    ]);

                                    $nomor++;
                                });

                        });
                })
                ->each(function ($personel) use ($year) {

                    /**
                 * all marketing must have four data each year
                 * wee need to create it first
                 */
                    for ($i = 1; $i < 5; $i++) {
                        MarketingFee::firstOrCreate([
                            "personel_id" => $personel->id,
                            "year" => $year,
                            "quarter" => $i,
                        ]);
                    }
                })

            /* generate fee target nominal sharing */
                ->when($generate_fee_sharing, function ($personels) use (
                    $generate_fee_target_nominal_sharing_action,
                    $quarter,
                    $year,
                ) {
                    DB::commit();
                    DB::beginTransaction();
                    return $personels
                        ->each(function ($personel) use (
                            $generate_fee_target_nominal_sharing_action,
                            $quarter,
                            $year,
                        ) {

                            $this->fee_target_sharing->query()
                                ->where("year", $year)
                                ->where("quarter", $quarter)
                                ->where("marketing_id", $personel->id)
                                ->forceDelete();

                            $payload = [
                                "personel_id" => $personel->id,
                                "year" => $year,
                                "quarter" => $quarter,
                            ];

                            $fee_target_nominal_sharing = $generate_fee_target_nominal_sharing_action($payload);
                            if ($fee_target_nominal_sharing == "non marketing") {
                                $this->info("<fg=red>generate fee target nominal sharing : purchaser</>");
                            } else {
                                $this->info("<fg=red>generate fee target nominal sharing : non purchaser </>");
                            }
                        });
                })

                ->each(function ($personel, $index) use (
                    $marketing_fee_per_marketing_action,
                    $marketig_fee_action,
                    $quarter,
                    &$nomor,
                    $year,
                ) {
                    if ($index == 0) {
                        $nomor = 1;
                    }
                    $marketing_fee = MarketingFee::query()
                        ->where("personel_id", $personel->id)
                        ->where("quarter", $quarter)
                        ->where("year", $year)
                        ->first();

                    $old_fee = [
                        "personel_id" => $personel->id,
                        "year" => $year,
                        "quarter" => $quarter,
                        "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                        "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                        "fee_reguler_settle_pending" => $marketing_fee->fee_reguler_settle_pending,
                        "fee_target_total" => $marketing_fee->fee_target_total,
                        "fee_target_settle" => $marketing_fee->fee_target_settle,
                        "fee_target_settle_pending" => $marketing_fee->fee_target_settle_pending,
                    ];

                    $payload = [
                        "personel_id" => $personel->id,
                        "year" => $year,
                        "quarter" => $quarter,
                        "sales_order" => null,
                        "is_settle" => false,
                    ];
                    $fee = $marketing_fee_per_marketing_action($payload);
                    $marketing_fee_updated = $marketig_fee_action(collect($fee)->toArray(), $marketing_fee);

                    $test = activity()
                        ->causedBy(auth()->id())
                        ->performedOn($marketing_fee_updated)
                        ->withProperties([
                            "old" => $old_fee,
                            "attributes" => $marketing_fee_updated,
                        ])
                        ->tap(function (Activity $activity) {
                            $activity->log_name = 'sync';
                        })
                        ->log('marketing point syncronize');

                    dump([
                        "nomor" => $nomor,
                        "personel_id" => $personel->id,
                        "name" => $personel->name,
                        "year" => $year,
                        "quarter" => $quarter,
                        "fee_reguler_total" => $marketing_fee_updated->fee_reguler_total,
                        "fee_reguler_settle" => $marketing_fee_updated->fee_reguler_settle,
                        "fee_reguler_settle_pending" => $marketing_fee_updated->fee_reguler_settle_pending,
                        "fee_target_total" => $marketing_fee_updated->fee_target_total,
                        "fee_target_settle" => $marketing_fee_updated->fee_target_settle,
                        "fee_target_settle_pending" => $marketing_fee_updated->fee_target_settle_pending,
                    ]);

                    $nomor++;
                });
            DB::commit();

            /* telegram notifcation */
            // telegram_notiication("Recalculate Fee Marketing Done!");
        } catch (\Throwable $th) {

            /* telegram notifcation */
            telegram_notiication("Oops! Spmeting wrong");

            DB::rollback();
            dd($th);
        }
    }
}
