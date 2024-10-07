<?php

namespace Modules\KiosDealer\Traits;

use Modules\KiosDealer\Http\Controllers\DealerLogController;

/**
 * 
 */
trait TraitName
{
    public function log($data){
        $dealer = new DealerLogController;
    }
}
