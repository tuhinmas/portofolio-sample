<?php

namespace Modules\KiosDealer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Contest\Actions\Point\ContestPointCalculationByContractAction;
use Modules\Contest\Entities\ContestParticipant;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\LogPhone\Entities\LogPhone;
use Modules\SalesOrder\Entities\SalesOrder;

class SubDealerToDealerTransferCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'sub_dealer:transfer_check';

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
        protected ContestParticipant $contest_participant,
        protected SalesOrder $sales_order,
        protected SubDealer $sub_dealer,
        protected LogPhone $log_phone,
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
        $nomor = 1;
        $cust_sub_id = $this->ask('Customer-ID-from?: ');
        $cust_id = $this->ask('Customer-ID-to?: ');

        $sub_dealer = $this->sub_dealer->query()
            ->withTrashed()
            ->where("sub_dealer_id", $cust_sub_id)
            ->first();

        $dealer = $this->dealer->query()
            ->where("dealer_id", $cust_id)
            ->first();

        if (!$sub_dealer) {
            $this->info("cust-sub-" . $cust_sub_id . " not found");
            return 0;
        }

        if (!$dealer) {
            $this->info("cust-" . $cust_id . " not found");
            return 0;
        }

        DB::beginTransaction();
        $sub_dealer->dealer_id = $dealer->id;
        $sub_dealer->save();
        $sub_dealer->delete();

        /**
         * update order from sub dealer to dealer
         */
        $this->sales_order->query()
            ->where("type", "2")
            ->where("store_id", $sub_dealer->id)
            ->where("model", "2")
            ->orderBy("order_number")
            ->get()
            ->each(function ($order) use ($sub_dealer, $dealer, &$nomor) {
                $order->store_id = $dealer->id;
                $order->store_as_sub_dealer = $sub_dealer->id;
                $order->model = "1";
                $order->save();

                dump([
                    "nomor" => $nomor,
                    "order_number" => $order->order_number,
                ]);

                ++$nomor;
            });

        /**
         * according to new rule dated 2023-07-31
         * all sub dealer contracts will be
         * transferred to dealers
         */
        $nomor = 0;
        $this->contest_participant->query()
            ->where("sub_dealer_id", $sub_dealer->id)
            ->get()
            ->each(function ($contract) use ($dealer, &$nomor) {
                $contract->dealer_id = $dealer->id;
                $contract->save();

                (new ContestPointCalculationByContractAction)($contract);

                $contract = $this->contest_participant->find($contract->id);
                dump([
                    "nomor" => $nomor,
                    "participant_status" => $contract->participant_status,
                    "participation_status" => $contract->participation_status,
                    "redeem_status" => $contract->redeem_status,
                    "redeem_date" => $contract->redeem_date,
                    "is_dealer_support" => $contract->is_dealer_support,
                    "registration_date" => $contract->registration_date,
                    "admitted_date" => $contract->admitted_date,
                    "previous_point" => $contract->previous_point,
                    "point" => $contract->point,
                    "adjustment_point" => $contract->adjustment_point,
                    "active_point" => $contract->active_point,
                    "redeemable_point" => $contract->redeemable_point,
                ]);

                ++$nomor;
            });

        $this->log_phone->query()
            ->where("model_id", $sub_dealer->id)
            ->where("type", "phone")
            ->lazyById()
            ->each(function ($log) {
                $sub_dealer->model_id = $sub_dealer->dealer_id;
                $sub_dealer->model = Dealer::class;
                $sub_dealer->save();
            });

        DB::commit();
    }
}
