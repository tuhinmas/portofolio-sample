<?php

namespace Modules\DataAcuan\Jobs;
 
use App\Models\Podcast;
use App\Services\AudioProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Repositories\FeeProductRepository;

class SyncFeeByProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year, $quarter, $productId, $byPersonel;

    public function __construct($year, $quarter, $productId, $byPersonel = true)
    {
        $this->year = $year;
        $this->quarter = $quarter;
        $this->productId = $productId;
        $this->byPersonel = $byPersonel;
        $this->onQueue('order');
    }

    public function handle(FeeProductRepository $feeProduct)
    {
        return $feeProduct->syncFeeByProduct($this->year, $this->quarter, $this->productId, $this->byPersonel);
    }
}