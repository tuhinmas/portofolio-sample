<?php

namespace Modules\SalesOrder\Console;

use Illuminate\Console\Command;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\KiosDealer\Entities\DealerGrading;

class OrderGradingSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'order:sync-grading';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync grading of order';

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
        SalesOrder::query()
            ->with([
                "subDealer",
                "invoice",
                "dealer",
            ])
            ->whereNull("grading_id")
            ->orderBy("order_number", "desc")
            ->get()

            ->filter(fn($order) => confirmation_time($order)->year == "2023")
            ->each(function ($order) {
                if ($order->model == "1") {

                    /* grading on confirm */
                    $dealer_grading = DealerGrading::query()
                        ->where("dealer_id", $order->store_id)
                        ->whereNotNull("grading_id")
                        ->where("created_at", "<=", confirmation_time($order))
                        ->orderBy("created_at", "desc")
                        ->first();

                    if (!$order->dealer) {
                        return true;
                    }
                    $grading_id = $order->dealer->grading_id;
                    if ($dealer_grading) {
                        $grading_id = $dealer_grading->grading_id;
                    }
                } else {
                    $grading_id = $order->subDealer->grading_id;
                }

                $order->grading_id = $grading_id;
                $order->save();

                dump([
                    "customer-id" => $order->dealer ? $order->dealer->dealer_id : $order->subDealer->sub_dealer_id,
                    "grading_id" => $grading_id,
                ]);
            });
    }
}
