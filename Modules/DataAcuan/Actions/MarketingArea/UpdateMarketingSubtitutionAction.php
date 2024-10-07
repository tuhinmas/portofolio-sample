<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Modules\Personel\Entities\LogFreeze;

class UpdateMarketingSubtitutionAction
{
    public function __invoke($replaced_personel, $replacemnet_marketing)
    {
        /**
         * cek log freeze of marketing,
         * if there exist update it
         */
        $log_freeze = LogFreeze::query()
            ->where("personel_id", $replaced_personel)
            ->whereNull("id_subtitute_personel")
            ->whereNull("freeze_end")
            ->orderBy("created_at")
            ->first();

        if ($log_freeze) {
            $log_freeze->id_subtitute_personel = $replacemnet_marketing;
            $log_freeze->save();
        }
    }
}
