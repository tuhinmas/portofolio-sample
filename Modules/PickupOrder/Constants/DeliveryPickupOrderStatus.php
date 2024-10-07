<?php

namespace Modules\PickupOrder\Constants;

class DeliveryPickupOrderStatus
{
    const RECEIVED = 2;
    const NOT_RECEIVED = 1;

    public static function labels(): array
    {
        return [
            self::RECEIVED => 'Diterima',
            self::NOT_RECEIVED => 'Belum Diterima',
        ];
    }

    public static function label(int $id)
    {   
        return static::labels()[$id];
    }

}

