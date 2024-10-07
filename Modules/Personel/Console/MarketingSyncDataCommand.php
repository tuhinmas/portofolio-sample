<?php

namespace Modules\Personel\Console;

use Illuminate\Console\Command;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Actions\Marketing\DeactivateMarketingAction;
use Modules\SalesOrder\Entities\SalesOrder;

class MarketingSyncDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'marketing:syn-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cek area marketing and marketing data.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected MarketingAreaDistrict $district,
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
        DeactivateMarketingAction $deactivate_action
    ) {
        $this->district->query()
            ->with([
                "personel.position",
            ])
            ->whereHas("personel", function ($QQQ) {
                return $QQQ->where("status", "3");
            })
            ->lazy()
            ->each(function ($district) use ($deactivate_action) {
                dump([
                    $district->district_id,
                    $district->personel_id,
                    $district->personel->name,
                    $district->personel->status,
                ]);

                /**
                 * deactivate action
                 */
                $deactivate_action($district->personel, null);

                /**
                 * order check
                 */
                $dealer = $this->dealer->query()
                    ->whereHas("adressDetail", function ($QQQ) use ($district) {
                        return $QQQ
                            ->where("district_id", $district->district_id)
                            ->where("type", "dealer");
                    })
                    ->get()
                    ->pluck("id")
                    ->toArray();

                $sub_dealer = $this->sub_dealer->query()
                    ->whereHas("addressDetail", function ($QQQ) use ($district) {
                        return $QQQ
                            ->where("district_id", $district->district_id)
                            ->where("type", "sub_dealer");
                    })
                    ->get()
                    ->pluck("id")
                    ->toArray();

                $this->sales_order->query()
                    ->where(function ($QQQ) use ($dealer, $sub_dealer) {
                        return $QQQ
                            ->where(function ($QQQ) use ($dealer, $sub_dealer) {
                                return $QQQ
                                    ->whereIn("store_id", $dealer)
                                    ->where("model", "1");
                            })
                            ->orWhere(function ($QQQ) use ($dealer, $sub_dealer) {
                                return $QQQ
                                    ->whereIn("store_id", $sub_dealer)
                                    ->where("model", "2");
                            });
                    })
                    ->whereIn("status", ["draft", "submited", "onhold", "reviewed"])
                    ->get()
                    ->each(function ($order) use ($district) {
                        if ($order->personel_id) {
                            if ($order->personel_id != $district->personel_id) {
                                dump($order->order_number);
                            }
                        }
                    });

            });
    }
}