<?php

namespace Modules\Personel\Actions\Applicator;

use Modules\Personel\Entities\Personel;

class UpdateApplicatorSupervisorAction
{
    public function __invoke($current_supervisor_id, $new_supervisor_id)
    {
        return Personel::query()
            ->applicator()
            ->where("supervisor_id", $current_supervisor_id)
            ->get()
            ->each(function ($applicator) use ($new_supervisor_id) {
                $applicator->supervisor_id = $new_supervisor_id;
                $applicator->save();
            });
    }
}
