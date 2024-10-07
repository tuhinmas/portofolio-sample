<?php

namespace Modules\Invoice\Console;

use Illuminate\Console\Command;
use Modules\Invoice\Entities\Invoice;

class PaymentStatusFixingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'invoice:paymnent-status-fixing';

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
        protected Invoice $invoice
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
        $nomor = 1;
        $this->invoice->query()
            ->whereHas("payment")
            ->where("payment_status", "<>", "settle")
            ->withSum("payment", "nominal")
            ->orderBy("created_at")
            ->lazy()
            ->each(function ($invoice) use (&$nomor) {
                dump([
                    "nomor" => $nomor,
                    "invoice" => $invoice->invoice,
                    "proforma _value" => $invoice->total + $invoice->ppn,
                    "payment" => $invoice->payment_sum_nominal,
                    "is_meet_payment" => $invoice->payment_sum_nominal >= $invoice->total + $invoice->ppn
                ]);

                if ($invoice->payment_sum_nominal >= $invoice->total + $invoice->ppn) {
                    $invoice->payment_status = "settle";
                    $invoice->save();
                }
                $nomor++;
            });
    }
}
