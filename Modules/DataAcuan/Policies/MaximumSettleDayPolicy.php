<?php

namespace Modules\DataAcuan\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class MaximumSettleDayPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}
