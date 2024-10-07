<?php

namespace Modules\Personel\Observers;

use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelSupervisorHistory;
use Modules\Personel\Actions\UpsertPersonelPositionHistoryAction;
use Modules\Personel\Actions\Marketing\MarketingDistrictSupervisorChangeAction;
use Modules\Personel\Actions\Marketing\MarketingSubRegionSupervisorChangeAction;

class PersonelObserver
{
    // public $afterCommit = true;
    public static $enabled = true;

    /**
     * Handle the User "created" event.
     *
     * @param    $personel
     * @return void
     */
    public function created(Personel $personel)
    {
        if (!self::$enabled) {
            return;
        }

        $personel_history = PersonelSupervisorHistory::query()
            ->where("personel_id", $personel->id)
            ->orderBy("change_at", "desc")
            ->first();

        if (!empty($personel->supervisor_id)) {
            if ($personel_history) {
                if (($personel_history->supervisor_id != $personel->supervisor_id)) {
                    PersonelSupervisorHistory::create([
                        "personel_id" => $personel->id,
                        "position_id" => $personel->position_id,
                        "supervisor_id" => $personel->supervisor_id,
                        "change_at" => now(),
                        "modified_by" => auth()->user()?->personel_id,
                    ]);
                }
            } else {
                PersonelSupervisorHistory::create([
                    "personel_id" => $personel->id,
                    "position_id" => $personel->position_id,
                    "supervisor_id" => $personel->supervisor_id,
                    "change_at" => now(),
                    "modified_by" => auth()->user()?->personel_id,
                ]);
            }
        }

        /* position on update */
        $data = [
            "personel_id" => $personel->id,
            "position_id" => $personel->position_id,
            "change_at" => now(),
        ];

        $upsert_personel_position = new UpsertPersonelPositionHistoryAction;
        $upsert_personel_position($data);
    }

    /**
     * Handle the User "updated" event.
     *
     * @param    $personel
     * @return void
     */
    public function updated(Personel $personel)
    {
        $personel_history = PersonelSupervisorHistory::query()
            ->where("personel_id", $personel->id)
            ->orderBy("change_at", "desc")
            ->first();

        if (!empty($personel->supervisor_id)) {
            if ($personel_history) {
                if (($personel_history->supervisor_id != $personel->supervisor_id)) {
                    PersonelSupervisorHistory::create([
                        "personel_id" => $personel->id,
                        "position_id" => $personel->position_id,
                        "supervisor_id" => $personel->supervisor_id,
                        "change_at" => now(),
                        "modified_by" => auth()->user()?->personel_id,
                    ]);
                }
            } else {
                PersonelSupervisorHistory::create([
                    "personel_id" => $personel->id,
                    "position_id" => $personel->position_id,
                    "supervisor_id" => $personel->supervisor_id,
                    "change_at" => now(),
                    "modified_by" => auth()->user()?->personel_id,
                ]);
            }
        }

        /* position on update */
        $data = [
            "personel_id" => $personel->id,
            "position_id" => $personel->position_id,
            "change_at" => now(),
        ];

        $upsert_personel_position = new UpsertPersonelPositionHistoryAction;
        $upsert_personel_position($data);
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param    $personel
     * @return void
     */
    public function deleted(Personel $personel)
    {
        //
    }

    /**
     * Handle the User "forceDeleted" event.
     *
     * @param    $personel
     * @return void
     */
    public function forceDeleted(Personel $personel)
    {
        //
    }
}
