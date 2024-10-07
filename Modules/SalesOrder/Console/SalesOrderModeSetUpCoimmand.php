<?php

namespace Modules\SalesOrder\Console;

use Illuminate\Console\Command;
use Modules\SalesOrder\Entities\SalesOrder;

class SalesOrderModeSetUpCoimmand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'sales_order:sales_mode_sync';

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
        protected SalesOrder $sales_order
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
        $this->sales_order->query()
            ->lazyById()
            ->each(function ($order) {
                $order->sales_mode = self::salesMode($order);
                $order->save();

                dump($order->sales_mode);
            });
    }

    public function salesMode($sales_order): string
    {
        $sales_mode = "marketing";
        switch (true) {
            case (bool) $sales_order->is_office:
                $sales_mode = "office";
                break;

            case (bool) $sales_order->counter_id:
                $sales_mode = "follow_up";
                break;

            default:
                break;
        }

        return $sales_mode;
    }
}
