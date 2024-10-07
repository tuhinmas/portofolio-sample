<?php

namespace Modules\SalesOrder\Actions;

use Modules\SalesOrder\Entities\FeeSharingSoOrigin;

class UpsertFeeSharingSoOriginAction
{
    public function __invoke(array $data, FeeSharingSoOrigin $fee_sharing_origin = null): FeeSharingSoOrigin
    {
        return FeeSharingSoOrigin::updateOrCreate(
            ["id" => $fee_sharing_origin?->id],
            $data
        );
    }
}
