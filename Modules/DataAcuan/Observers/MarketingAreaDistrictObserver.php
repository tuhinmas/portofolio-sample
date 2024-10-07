<?php

namespace Modules\DataAcuan\Observers;

use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Actions\MarketingArea\DeletedDistrictAction;
use Modules\DataAcuan\Jobs\MarketingAreaDistrict\DeletedDistrictJob;
use Modules\DataAcuan\Jobs\MarketingAreaDistrict\UpdatedDistrictJob;

class MarketingAreaDistrictObserver
{
    public function deleted(MarketingAreaDistrict $district)
    {
        DeletedDistrictJob::dispatch($district);
    }

    public function updated(MarketingAreaDistrict $district)
    {
    }
}
