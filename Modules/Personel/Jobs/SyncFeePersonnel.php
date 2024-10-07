<?php

namespace Modules\Personel\Jobs;
 
use App\Models\Podcast;
use App\Services\AudioProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Modules\Personel\Repositories\FeeSharingGenerator;
use Modules\Personel\Repositories\MarketingFeeCalculate;

class SyncFeePersonnel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year, $personelId;

    public function __construct($year, $personelId)
    {
        $this->year = $year;
        $this->personelId = $personelId;
    }

    public function handle()
    {
        resolve(FeeSharingGenerator::class)->handle($this->year, $this->personelId);
        resolve(MarketingFeeCalculate::class)->handle($this->year, $this->personelId);
    }
}