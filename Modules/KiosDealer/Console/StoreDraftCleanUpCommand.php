<?php

namespace Modules\KiosDealer\Console;

use Illuminate\Console\Command;
use Modules\KiosDealer\Entities\StoreTemp;

class StoreDraftCleanUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'store:clean-up';

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
        protected StoreTemp $store_temp
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
        $this->store_temp->query()
            ->where("created_at", "<=", now()->subDays(2))
            ->where("status", "draft")
            ->lazyById()
            ->each(function ($store_temp) {
                dump([
                    $store_temp->id,
                    (string) $store_temp->created_at,
                ]);
                $store_temp->delete();
            });
    }
}
