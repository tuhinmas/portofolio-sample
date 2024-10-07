<?php

namespace Modules\Personel\Observers;

use Modules\Personel\Entities\Marketing;
use Modules\Personel\Entities\PersonelSupervisorHistory;

class MarketingObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param    $personel
     * @return void
     */
    public function created(Marketing $marketing)
    {
        //
    }

    /**
     * Handle the User "updated" event.
     *
     * @param    $personel
     * @return void
     */
    public function updated(Marketing $marketing)
    {
        $personel_history = PersonelSupervisorHistory::query()
            ->where("personel_id", $marketing->id)
            ->orderBy("change_at", "desc")
            ->first();

        if (!empty($marketing->supervisor_id)) {
            if ($personel_history) {

                if ($personel_history->supervisor_id != $marketing->supervisor_id) {
                    PersonelSupervisorHistory::create([
                        "personel_id" => $marketing->id,
                        "position_id" => $marketing->position_id,
                        "supervisor_id" => $marketing->supervisor_id,
                        "change_at" => now(),
                        "modified_by" => auth()->user()?->personel_id,
                    ]);
                }
            } else {
                PersonelSupervisorHistory::create([
                    "personel_id" => $marketing->id,
                    "position_id" => $marketing->position_id,
                    "supervisor_id" => $marketing->supervisor_id,
                    "change_at" => now(),
                    "modified_by" => auth()->user()?->personel_id,
                ]);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param    $personel
     * @return void
     */
    public function deleted(Marketing $marketing)
    {
        //
    }

    /**
     * Handle the User "forceDeleted" event.
     *
     * @param    $personel
     * @return void
     */
    public function forceDeleted(Marketing $marketing)
    {
        //
    }
}
