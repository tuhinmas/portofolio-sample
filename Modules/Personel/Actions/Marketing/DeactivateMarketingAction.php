<?php

namespace Modules\Personel\Actions\Marketing;

use App\Traits\MarketingArea;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Modules\Personel\Events\PersonelInactiveEvent;

class DeactivateMarketingAction
{
    use MarketingArea;

    /**
     * Deactivate marketing only
     *
     * @param Personel $personel
     * @param [type] $status
     * @return void
     */
    public function __invoke(Personel $personel, $previous_status = null)
    {        
        if ($personel->status != "3") {
            return;
        }

        /**
         * 1. area marketing takover by supervisor
         * 2. area applicator set to null
         * 3. dealer take over
         * 4. sub dealer take over
         * markating area trait
         */
        $this->personelHasArea($personel->id);

        /**
         * 1. order unconfirm take over
         * 2. fee recalculation
         * 3. point recalculation
         * 4. area take over
         * 
         */
        PersonelInactiveEvent::dispatch($personel, $previous_status);

        /**
         * User account will suspend
         */
        $user = User::where('personel_id', $personel->id)->first();
        if ($user) {
            $user->delete();
        }
    }
}
