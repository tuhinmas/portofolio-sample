<?php

namespace Modules\DataAcuan\Jobs\DealerGradeSuggestion;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\DataAcuan\Actions\DealerGradeSuggestion\SyncAllDealerGradeSuggestionAction;

class SyncAllDealerGradeSuggestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 7;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SyncAllDealerGradeSuggestionAction $sync_all_dealer_grade_suggestion)
    {
        /**
         * pending
         */
        // $sync_all_dealer_grade_suggestion();
    }
}
