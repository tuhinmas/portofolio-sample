<?php

namespace Modules\DataAcuan\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Modules\Personel\Entities\LogFreeze;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogFreezeHistoryListener
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
    public function handle($event)
    {
        /**
         * cek log freeze of marketing,
         * if there exist update it
         */
        $log_freeze = LogFreeze::query()
            ->where("personel_id", $event->replaced_personel)
            ->whereNull("id_subtitute_personel")
            ->whereNull("freeze_end")
            ->orderBy("created_at")
            ->first();
        
        if ($log_freeze) {
            $log_freeze->id_subtitute_personel = $event->marketing_area_district->personel_id;
            $log_freeze->save();
        }

        return $log_freeze;
    }
}
