<?php

namespace Modules\Personel\Actions;

use Modules\Personel\Entities\PersonelPositionHistory;

class UpsertPersonelPositionHistoryAction
{
    public function __invoke(array $data, PersonelPositionHistory $personel_position_history = null): PersonelPositionHistory
    {
        return PersonelPositionHistory::updateOrCreate(
            ['id' => $personel_position_history?->$id],
            $data
        );
    }
}
