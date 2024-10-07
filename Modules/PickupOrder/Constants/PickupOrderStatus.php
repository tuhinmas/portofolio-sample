<?php

namespace Modules\PickupOrder\Constants;

class PickupOrderStatus
{
    const LOADING = 1;
    const SENDING = 2;
    const DONE = 3;
    const CANCELED = 4;

    public static function labels(): array
    {
        return [
            self::LOADING => 'Loading',
            self::SENDING => 'Dikirim',
            self::DONE => 'Selesai',
            self::CANCELED => 'Dibatalkan',
        ];
    }

    public static function label(int $id)
    {   
        return static::labels()[$id];
    }

}

