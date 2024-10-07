<?php

namespace Modules\DataAcuan\Console;

use Illuminate\Console\Command;
use Modules\DataAcuan\Actions\MarketingArea\SyncRetailerToMarketingAction;
use Modules\DataAcuan\Actions\MarketingArea\SyncDistributorToMarketingAction;

class SyncRetailerAndDistributorToMarketingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'sync:retailer-to-marketing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'syn dealer retailer and sub dealer to marketing area.';

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
    public function handle(
        SyncDistributorToMarketingAction $sync_distributor_action,
        SyncRetailerToMarketingAction $sync_retailer_action,
        )
    {
        dump([
            $sync_distributor_action->execute(),
            $sync_retailer_action->execute(),
        ]);
        
    }
}
