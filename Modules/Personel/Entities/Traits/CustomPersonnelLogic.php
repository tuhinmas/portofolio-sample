<?php

namespace Modules\Personel\Entities\Traits;

trait CustomPersonnelLogic
{
    public static $withoutAppends = false;

    public function scopeWithoutAppends($query)
    {
        self::$withoutAppends = true;
        return $query;
    }
    
    protected function getArrayableAppends()
    {
        if (self::$withoutAppends) {
            return [];
        }
        return parent::getArrayableAppends();
    }
}
