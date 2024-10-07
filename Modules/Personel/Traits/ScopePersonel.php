<?php

namespace Modules\Personel\Traits;

trait ScopePersonel
{
    public function scopeApplicator($query)
    {
        return $query->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", applicator_positions());
        });
    }
}
