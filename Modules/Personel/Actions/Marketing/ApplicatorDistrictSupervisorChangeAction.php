<?php

namespace Modules\Personel\Actions\Marketing;

use Modules\DataAcuan\Actions\MarketingArea\RevokeApplicatorFromAreaAction;
use Modules\Personel\Entities\Personel;

class ApplicatorDistrictSupervisorChangeAction
{
    /**
     * applicator will revoke from district if supervisor change
     *
     * @param Personel $personel
     * @return void
     */
    public function __invoke(Personel $personel)
    {
        return (new RevokeApplicatorFromAreaAction)($personel);
    }
}
