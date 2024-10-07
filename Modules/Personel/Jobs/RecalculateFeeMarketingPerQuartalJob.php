<?php

namespace Modules\Personel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Personel\Actions\Marketing\UpsertMarketingFeeAction;
use Modules\SalesOrderV2\Actions\CalculateMarketingFeeByMarketingAction;

class RecalculateFeeMarketingPerQuartalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $personel_id, $year, $quarter;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 900;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 9;

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
    public function __construct($personel_id, $year, $quarter)
    {
        $this->personel_id = $personel_id;
        $this->quarter = $quarter;
        $this->year = $year;
        $this->onQueue('order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        CalculateMarketingFeeByMarketingAction $calculate_marketing_fee_per_quarter_action,
        UpsertMarketingFeeAction $upsert_marketing_fee_action,
    ) {
        $payload = [
            "personel_id" => $this->personel_id,
            "year" => $this->year,
            "quarter" => $this->quarter,
            "sales_order" => null,
            "is_settle" => false
        ];

        $upsert_marketing_fee_action($calculate_marketing_fee_per_quarter_action($payload));
    }
}
