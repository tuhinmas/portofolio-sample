<?php

namespace Modules\SalesOrder\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\SalesOrder\Actions\Order\GetStatusFeeForOrderDependSuggestionAction;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderStatusFeeShould;

class SalesOrderStatusFeeUnmatchCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'order:status_fee_unmatch_check';
    protected $status_fee_before;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sales order status fee check, if unmatch then display it.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected SalesOrderStatusFeeShould $sales_order_status_fee,
        protected SalesOrder $sales_order,
        protected StatusFee $status_fee,
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
    ) {
        $year = now()->year;
        $quarter = now()->quarter;
        $from_first_order = false;
        $store_id = null;
        $compare = false;

        if ($this->confirm('Check from first order?', false)) {
            $from_first_order = true;
        } else {
            $year = $this->ask('Which year?');
            $quarter = $this->ask('Which quarter?');
        }

        if ($this->confirm('Only spesific dealer / sub dealer?', true)) {
            $store_id = $this->ask('Which dealer / Sub Dealer ID ?');
        }
       
        if ($this->confirm('compare order and status fee should?', true)) {
            $compare = true;
        }

        $this->sales_order_status_fee->query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "statusFee",
                        "invoice",
                    ]);
                },
                "statusFee",
            ])
            ->whereHas("salesOrder", function ($QQQ) use ($year, $quarter, $from_first_order, $store_id) {
                return $QQQ
                    ->consideredOrder()
                    ->when(!$from_first_order, function ($QQQ) use ($year, $quarter) {
                        return $QQQ
                            ->salesByYear($year)
                            ->salesByQuarter($quarter);
                    })
                    ->when($store_id, function ($QQQ) use ($store_id) {
                        return $QQQ->where("store_id", $store_id);
                    });
            })
            ->get()
            ->sortBy(function ($status_fee) {
                return confirmation_time($status_fee->salesOrder);
            })
            ->each(function ($status_fee) use ($status_fee_should_depend_suggestion, $compare) {
                if (!self::statusFeeMatch($status_fee)) {
                    $this->info("===========================================================================");
                    dd([
                        "s_id" => $status_fee->salesOrder->id,
                        "store_id" => $status_fee->salesOrder->store_id,
                        "marketing_id" => $status_fee->salesOrder->personel_id,
                        "order_number" => $status_fee->salesOrder->order_number,
                        "confirm_time" => confirmation_time($status_fee->salesOrder)->format("Y-m-d H:i:s"),
                        "status_fee_on_order" => $status_fee->salesOrder->statusFee->name,
                        "status_fee_on_should" => $status_fee->statusFee->name,
                        "status_fee_before" => $this->status_fee_before,
                        "is_office" => $status_fee->salesOrder->is_office,
                        "type" => $status_fee->salesOrder->type,
                    ]);
                } else {
                    if ($compare) {
                        if ($status_fee->salesOrder->status_fee_id != $status_fee->status_fee_id) {
                            $this->info("===========================================================================");
                            dd([
                                "s_id" => $status_fee->salesOrder->id,
                                "store_id" => $status_fee->salesOrder->store_id,
                                "marketing_id" => $status_fee->salesOrder->personel_id,
                                "order_number" => $status_fee->salesOrder->order_number,
                                "confirm_time" => confirmation_time($status_fee->salesOrder)->format("Y-m-d H:i:s"),
                                "status_fee_on_order" => $status_fee->salesOrder->statusFee->name,
                                "status_fee_on_should" => $status_fee->statusFee->name,
                                "status_fee_before" => $this->status_fee_before,
                                "is_office" => $status_fee->salesOrder->is_office,
                                "type" => $status_fee->salesOrder->type,
                            ]);
                        }
                    }
                }

                $this->status_fee_before = $status_fee->statusFee->name;
                dump([
                    "order_number_current" => $status_fee->salesOrder->order_number,
                    "status_fee_current" => $status_fee->salesOrder->statusFee->name,
                    "status_fee_should" => $status_fee->statusFee->name,
                    "confirmed_at" => confirmation_time($status_fee->salesOrder)->format("Y-m-d H:i:s"),
                ]);
            });
    }

    public static function statusFeeMatch(SalesOrderStatusFeeShould $status_fee_should)
    {
        $L1 = StatusFee::query()
            ->where("name", "L1")
            ->first();

        $L2 = StatusFee::query()
            ->where("name", "L2")
            ->first();

        $L3 = StatusFee::query()
            ->where("name", "L3")
            ->first();

        $status_fee_reguler = StatusFee::query()
            ->where("name", "R")
            ->first();

        $status_fee_before = SalesOrderStatusFeeShould::query()
            ->with([
                "salesOrder",
            ])
            ->whereHas("salesOrder", function ($QQQ) use ($status_fee_should) {
                return $QQQ->where("store_id", $status_fee_should->salesOrder->store_id);
            })
            ->where("confirmed_at", "<=", Carbon::parse($status_fee_should->confirmed_at))
            ->orderBy("confirmed_at", "desc")
            ->first();

        $is_match = false;
        if ($status_fee_should->salesOrder->is_office && $status_fee_should->status_fee_id == $status_fee_reguler->id) {
            $is_match = true;
        } else {
            if ($status_fee_before) {
                if ($status_fee_before->salesOrder->is_office) {

                    $status_fee_before_more = SalesOrderStatusFeeShould::query()
                        ->with([
                            "salesOrder",
                        ])
                        ->whereHas("salesOrder", function ($QQQ) use ($status_fee_before) {
                            return $QQQ->where("store_id", $status_fee_before->salesOrder->store_id);
                        })
                        ->where("confirmed_at", "<=", $status_fee_before->confirmed_at)
                        ->orderBy("confirmed_at", "desc")
                        ->first();

                    if (!$status_fee_before_more) {
                        if ($status_fee_before->status_fee_id == $L1->id) {
                            $is_match = true;
                        }
                    } else {
                        while ($status_fee_before_more->is_office) {
                            $before = SalesOrderStatusFeeShould::query()
                                ->with([
                                    "salesOrder",
                                ])
                                ->whereHas("salesOrder", function ($QQQ) use ($status_fee_before_more) {
                                    return $QQQ->where("store_id", $status_fee_before_more->salesOrder->store_id);
                                })
                                ->where("confirmed_at", "<=", $status_fee_before_more->confirmed_at)
                                ->orderBy("confirmed_at", "desc")
                                ->first();

                            if (!$before) {
                                break;
                            } else {
                                $status_fee_before_more = $before;
                            }
                        }

                        if ($status_fee_before_more->is_office) {
                            if ($status_fee_before->status_fee_id == $L1->id) {
                                $is_match = true;
                            }
                        } else {

                            if (Carbon::parse($status_fee_should->confirmed_at)->format("Y-m-d") == Carbon::parse($status_fee_before->confirmed_at)->format("Y-m-d")) {
                                if ($status_fee_should->status_fee_id == $status_fee_before->status_fee_id) {
                                    return true;
                                }
                            }

                            switch ($status_fee_should->status_fee_id) {
                                case $L1->id:
                                    if ($status_fee_should->salesOrder->personel_id != $status_fee_before_more->salesOrder->personel_id) {
                                        $is_match = true;
                                    }
                                    break;

                                case $L2->id:
                                    if ($status_fee_before_more->status_fee_id == $L1->id) {
                                        $is_match = true;
                                    }
                                    break;

                                case $L3->id:
                                    if ($status_fee_before_more->status_fee_id == $L2->id) {
                                        $is_match = true;
                                    }
                                    break;

                                case $status_fee_reguler->id:
                                    if ($status_fee_before_more->status_fee_id == $L3->id || $status_fee_reguler->id) {
                                        $is_match = true;
                                    }
                                    break;

                                default:
                                    $is_match = false;
                                    break;
                            }
                        }
                    }

                } else {

                    if (Carbon::parse($status_fee_should->confirmed_at)->format("Y-m-d") == Carbon::parse($status_fee_before->confirmed_at)->format("Y-m-d")) {
                        if ($status_fee_should->status_fee_id == $status_fee_before->status_fee_id) {
                            return true;
                        }
                    }

                    switch ($status_fee_should->status_fee_id) {
                        case $L1->id:
                            if ($status_fee_should->salesOrder->personel_id != $status_fee_before->salesOrder->personel_id) {
                                $is_match = true;
                            }
                            break;

                        case $L2->id:
                            if ($status_fee_before->status_fee_id == $L1->id) {
                                $is_match = true;
                            }
                            break;

                        case $L3->id:
                            if ($status_fee_before->status_fee_id == $L2->id) {
                                $is_match = true;
                            }
                            break;

                        case $status_fee_reguler->id:
                            if ($status_fee_before->status_fee_id == $L3->id || $status_fee_reguler->id) {
                                $is_match = true;
                            }
                            break;

                        default:
                            $is_match = false;
                            break;
                    }
                }
            } else {
                if ($status_fee_should->status_fee_id == $status_fee_reguler->id) {
                    $is_match = true;
                }
            }
        }
        return $is_match;
    }
}
