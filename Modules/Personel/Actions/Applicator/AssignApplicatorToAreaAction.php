<?php

namespace Modules\Personel\Actions\Applicator;

use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;

class AssignApplicatorToAreaAction
{
    public function __construct(
        protected MarketingAreaDistrict $area_district,
        protected Position $position,
    ) {}
    public function __invoke(Personel $personel)
    {
        /**
         * if personel is applicator, then will be assign to marketing area
         * that does not have applicator
         */
        $applicator_positions = $this->position->query()
            ->whereIn("name", applicator_positions())
            ->get()
            ->pluck("id")
            ->toArray();

        if (in_array($personel->position_id, $applicator_positions)) {

            $this->area_district->query()
                ->where("personel_id", $personel->supervisor_id)
                ->whereNull("applicator_id")
                ->get()
                ->each(function ($area) use ($personel) {
                    $area->applicator_id = $personel->id;
                    $area->save();
                });
        }

    }
}
