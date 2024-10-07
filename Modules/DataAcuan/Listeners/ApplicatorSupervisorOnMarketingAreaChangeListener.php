<?php

namespace Modules\DataAcuan\Listeners;

use Modules\DataAcuan\Events\MarketingAreaOnChangeEvent;
use Modules\Personel\Entities\Personel;

class ApplicatorSupervisorOnMarketingAreaChangeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MarketingAreaOnChangeEvent $event)
    {

        /**
         * PENDING
         * Rule change
         */
        // $applicator = Personel::find($event->marketing_area_district->applicator_id);

        // /**
        //  * applicator supervisor is according marketing
        //  * on this area, if marketing area change,
        //  * applicator supervisor also change
        //  */
        // if ($applicator) {
        //     $applicator->supervisor_id = $event->marketing_area_district->personel_id;
        //     $applicator->save();
        // }
    }
}
