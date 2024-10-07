<?php

namespace Modules\KiosDealer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;

class StatusFeeDealerFixingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'dealer:sync-status-fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
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
    public function handle()
    {
        /* default status fee */
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();
        $status_fees = DB::table('status_fee')->whereNull("deleted_at")->get();

        $this->dealer->query()
            ->with("salesOrder", function ($QQQ) {
                return $QQQ
                    ->with("invoice")
                    ->consideredOrder();
            })
            ->whereNull("status_fee")
            ->orderBy("dealer_id")
            ->lazyById()
            ->each(function ($dealer) use ($status_fee, $status_fees) {
                $status_fee_id = $status_fee->id;
                if (!$dealer->salesOrder->isEmpty()) {
                    $status_fee_id = $dealer
                        ->salesOrder
                        ->sortByDesc(function ($order) {
                            return confirmation_time($order);
                        })
                        ->first()
                        ->status_fee_id;
                }

                $dealer->status_fee = $status_fee_id;
                $dealer->save();
                dump([
                    "dealer_id" => $dealer->dealer_id,
                    "status_fee" => ($dealer->status_fee == $status_fee->id ? $status_fee->name : $status_fees->filter(fn($fee) => $fee->id == $status_fee_id)->first()->name),
                ]);
            });

        $this->info("------------------sub dealer-----------------");

        $this->sub_dealer->query()
            ->with("salesOrder", function ($QQQ) {
                return $QQQ->consideredOrder();
            })
            ->whereNull("status_fee")
            ->orderBy("sub_dealer_id")
            ->lazyById()
            ->each(function ($sub_dealer) use ($status_fee, $status_fees) {
                $status_fee_id = $status_fee->id;
                if (!$sub_dealer->salesOrder->isEmpty()) {
                    $status_fee_id = $sub_dealer
                        ->salesOrder
                        ->sortByDesc(function ($order) {
                            return confirmation_time($order);
                        })
                        ->first()
                        ->status_fee_id;
                }

                $sub_dealer->status_fee = $status_fee_id;
                $sub_dealer->save();
                dump([
                    "sub_dealer_id" => $sub_dealer->sub_dealer_id,
                    "status_fee" => ($sub_dealer->status_fee == $status_fee->id ? $status_fee->name : $status_fees->filter(fn($fee) => $fee->id == $status_fee_id)->first()->name),
                ]);
            });
    }
}
