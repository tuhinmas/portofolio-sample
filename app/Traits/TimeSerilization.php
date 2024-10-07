<?php

namespace App\Traits;

use DateTimeInterface;

/**
 *  timestamps serializing
 */
trait TimeSerilization
{
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
