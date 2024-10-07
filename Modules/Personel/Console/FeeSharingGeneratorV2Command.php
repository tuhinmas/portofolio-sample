<?php

namespace Modules\Personel\Console;

use Illuminate\Console\Command;

class FeeSharingGeneratorV2Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'fee:generate_fee_sharing_v2';

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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
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

            if (!$this->confirm('Regenerate fee sharing for previous Quarter?', true)) {
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

                        $active_contract = $this->distributorActiveContract($order->store_id, confirmation_time($order)->format("Y-m-d"));
                        if ($active_contract) {
                            $this->info("<fg=red>Sales to distributor active</>");
                            return true;
                        }

                        /* recalculate point per product */
                        $this->feeMarketingPerProductCalculator($order);

                        /* fee sharing generator */
                        if ($console) {
                            dump($this->feeSharingOriginGenerator($order) . " " . $order->order_number);
                        } else {
                            $this->feeSharingOriginGenerator($order);
                        }

                        /* fee target sharing generator */
                        // $console ? dump($this->feeTargetSharingOriginGenerator($order)) : $this->feeTargetSharingOriginGenerator($order);
                    });
            });
    }

}
