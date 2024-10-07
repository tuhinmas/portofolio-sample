<?php

namespace Modules\Personel\Listeners;

use Carbon\Carbon;
use Modules\Personel\Entities\LogFreeze;
use Modules\Personel\Events\PersonelFreezeEvent;

class PersonelFreezeListener
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
    public function handle(PersonelFreezeEvent $event)
    {
        $log_freeze = null;
        if ($event->request->status == "2") {
            $log_freeze = LogFreeze::create([
                "personel_id" => $event->personel->id,
                "freeze_start" => Carbon::now()->format("Y-m-d"),
                "freeze_end" => null,
                "after_freeze" => null,
                "id_subtitute_personel" => null,
                "user_id" => auth()->id(),
            ]);
        } else if ($event->personel->status == "2") {
            $log_freeze = LogFreeze::query()
                ->where("personel_id", $event->personel->id)
                ->whereNull("freeze_end")
                ->orderBy("created_at")
                ->first();

            if ($log_freeze) {
                $log_freeze->freeze_end = Carbon::now()->format("Y-m-d");
                $log_freeze->after_freeze = $event->request->status;
                $log_freeze->save();
            }
        }

        return $log_freeze;
    }
}
