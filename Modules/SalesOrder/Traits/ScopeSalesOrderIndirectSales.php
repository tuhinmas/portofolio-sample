<?php

namespace Modules\SalesOrder\Traits;

use Modules\Contest\Entities\ContestParticipant;

/**
 *
 */
trait ScopeSalesOrderIndirectSales
{

    public function scopeIndirectAccordingDate($query, $operator = "=", $date_time = null)
    {
        return $query->whereDate("date", $operator, $date_time);
    }
}
