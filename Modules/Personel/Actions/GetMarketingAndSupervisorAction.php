<?php

namespace Modules\Personel\Actions;

use App\Traits\ChildrenList;
use Modules\Personel\Entities\Personel;

class GetMarketingAndSupervisorAction
{
    use ChildrenList;

    public function __invoke($personel_id, $date)
    {
        $marketing_supervisor = $this->parentPersonel($personel_id, $date); 
        return Personel::query()
            ->with([
                "position",
            ])
            ->whereIn("id", $marketing_supervisor)
            ->get();
    }
}
