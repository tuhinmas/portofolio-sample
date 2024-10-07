<?php

namespace Modules\KiosDealer\Traits;

use Illuminate\Support\Facades\DB;

trait ScopeSubDealer
{
    public function scopeMarketing($query, $parameter)
    {
        $is_mm = DB::table('personels as p')
            ->join("positions as po", "p.position_id", "po.id")
            ->whereIn("po.name", position_mm())
            ->where("p.id", $parameter)
            ->where("p.status", "1")
            ->first();

        return $query->where(function ($QQQ) use ($parameter, $is_mm) {
            return $QQQ
                ->where("personel_id", $parameter)
                ->when($is_mm, function ($QQQ) {
                    return $QQQ->orWhereNull("personel_id");
                });
        });
    }
}
