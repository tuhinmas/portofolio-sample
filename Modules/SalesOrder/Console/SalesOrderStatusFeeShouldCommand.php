<?php

namespace Modules\SalesOrder\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\SalesOrder\Actions\Order\GetStatusFeeForOrderDependSuggestionAction;
use Modules\SalesOrder\Actions\Order\GetStatusFeeForOrderSuggestionAction;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderStatusFeeShould;

class SalesOrderStatusFeeShouldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'order:status_fee_should_be';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected SalesOrderStatusFeeShould $sales_order_status_fee,
        protected SalesOrder $sales_order,
        protected SubDealer $sub_dealer,
        protected Dealer $dealer,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(
        GetStatusFeeForOrderDependSuggestionAction $status_fee_should_depend_suggestion,
        GetStatusFeeForOrderSuggestionAction $status_fee_should,
    ) {
        $status_fee_update = false;
        $store_id = null;
        $from_first_order = false;

        $year = now()->year;
        $quarter = now()->quarter;

        if ($this->confirm('Check from first order?', false)) {
            $from_first_order = true;
        } else {
            $year = $this->ask('Which year?');
            $quarter = $this->ask('Which quarter?');
        }

        if ($this->confirm('Only spesific dealer / sub dealer?', true)) {
            $store_id = $this->ask('Which dealer / Sub Dealer ID ?');
        }

        if ($this->confirm('Update sales order status fee?', false)) {
            $status_fee_update = true;
        }

        /**
         * clear status fee should
         */
        $this->sales_order_status_fee->query()
            ->whereHas("salesOrder", function ($QQQ) use ($year, $quarter, $store_id, $from_first_order) {
                return $QQQ
                    ->consideredOrder()
                    ->when($store_id, function ($QQQ) use ($store_id) {
                        return $QQQ->where("store_id", $store_id);
                    })
                    ->when($from_first_order, function ($QQQ) {
                        return $QQQ;
                    })
                    ->when(!$from_first_order, function ($QQQ) use ($year, $quarter) {
                        return $QQQ
                            ->salesByYear($year)
                            ->salesByQuarter($quarter);
                    });
            })
            ->update([
                "is_checked" => false
            ]);

        /**
         * sales order status fee update
         */
        $sales_orders = $this->sales_order->query()
            ->with([
                "dealer",
                "invoice",
                "statusFee",
                "statusFeeShould",
            ])
            ->when($store_id, function ($QQQ) use ($store_id) {
                return $QQQ->where("store_id", $store_id);
            })
            ->consideredOrder()
            ->when($from_first_order, function ($QQQ) {
                return $QQQ;
            })
            ->when(!$from_first_order, function ($QQQ) use ($year, $quarter) {
                return $QQQ
                    ->salesByYear($year)
                    ->salesByQuarter($quarter);
            })
            ->orderByRaw("order_number")
            ->get()
            ->sortBy(function ($order) {
                return confirmation_time($order);
            })
            ->each(function ($order) use (
                $status_fee_should_depend_suggestion,
                $status_fee_should,
                $status_fee_update,
            ) {
                $status_fee_should = $status_fee_should_depend_suggestion($order);

                if (!$status_fee_should) {
                    dd($status_fee_should);
                }

                $status_fee = DB::table('status_fee')
                    ->where("id", $status_fee_should)
                    ->first();

                $this->sales_order_status_fee->updateOrCreate([
                    "sales_order_id" => $order->id,
                ], [
                    "status_fee_id" => $status_fee_should,
                    "confirmed_at" => confirmation_time($order),
                    "is_checked" => true
                ]);

                /* update status fee order */
                if ($status_fee_update) {
                    $order->status_fee_id = $status_fee_should;
                    if ($order->isDirty("status_fee_id")) {
                        $order->save();
                        if ($order->model == "1") {
                            $dealer = $this->dealer->find($order->store_id);
                            if ($dealer) {
                                $dealer->status_fee = $status_fee_should;
                                $dealer->save();
                            }
                        } else {
                            $sub_dealer = $this->sub_dealer->find($order->store_id);
                            if ($sub_dealer) {
                                $sub_dealer->status_fee = $status_fee_should;
                                $sub_dealer->save();
                            }
                        }
                        $order->refresh();
                    }
                }

                dump([
                    "id" => $order->id,
                    "status_fee_should" => $status_fee_should,
                    "marketing_id" => $order->personel_id,
                    "order_number" => $order->order_number,
                    "confirm_time" => confirmation_time($order)->format("Y-m-d H:i:s"),
                    "staus_fee_should" => $status_fee->name,
                    "is_office" => $order->is_office,
                    "type" => $order->type,
                    "confirmed_at" => confirmation_time($order)->format("Y-m-d H:i:s"),
                ]);
                $this->info("=====================================================================");
            });

        dd($sales_orders->count());
    }
}
