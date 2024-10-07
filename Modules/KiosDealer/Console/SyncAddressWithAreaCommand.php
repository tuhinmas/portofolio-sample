<?php

namespace Modules\KiosDealer\Console;

use Illuminate\Console\Command;
use Modules\Address\Entities\Address;
use Modules\Address\Entities\AddressTemp;
use Modules\KiosDealer\Actions\Address\SyncAddressTempWithAreaAction;
use Modules\KiosDealer\Actions\Address\SyncAddressWithAreaAction;

class SyncAddressWithAreaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'dealer:sync-address-with-area';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fill district area on address';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected Address $address,
        protected AddressTemp $address_temp,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SyncAddressWithAreaAction $sync_action, SyncAddressTempWithAreaAction $sync_address_temp_action)
    {
        $nomor = 1;
        $this->address->query()
            ->whereNull("area_id")
            ->orderBy("district_id")
            ->cursor()
            ->each(function ($address) use ($sync_action, &$nomor) {
                $sync_action($address);
                $address->refresh();
                dump([
                    $nomor,
                    "address fix",
                    $address->district_id,
                    $address->area_id,
                    $address->sub_region_id,
                    $address->region_id,
                ]);
                $nomor++;
            });

        $nomor = 1;
        $this->address_temp->query()
            ->whereNull("area_id")
            ->orderBy("district_id")
            ->cursor()
            ->each(function ($address) use ($sync_address_temp_action, &$nomor) {
                $sync_address_temp_action($address);
                $address->refresh();
                dump([
                    $nomor,
                    "address temp",
                    $address->district_id,
                    $address->area_id,
                    $address->sub_region_id,
                    $address->region_id,
                ]);
                $nomor++;
            });
    }
}
