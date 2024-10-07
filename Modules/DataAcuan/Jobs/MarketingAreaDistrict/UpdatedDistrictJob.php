<?php

namespace Modules\DataAcuan\Jobs\MarketingAreaDistrict;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Authentication\Entities\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Actions\MarketingArea\DistrictMarketingChangeAction;

class UpdatedDistrictJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 450;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 2;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected MarketingAreaDistrict $district, 
        protected $old_marketing_id,
        protected User $user
        )
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DistrictMarketingChangeAction $updated_district_action)
    {
        $updated_district_action($this->district, $this->old_marketing_id, $this->user);
    }
}
