<?php

namespace Modules\ReceivingGood\Policies;

use App\Traits\ResponseHandler;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Authentication\Entities\User;
use Modules\ReceivingGood\Entities\ReceivingGood;

class ReceivingGoodPolicy
{
    use HandlesAuthorization;
    use ResponseHandler;

    /**
     * Determine whether the user can view the list of plants.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the ReceivingGood.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, ReceivingGood $receiving_good)
    {
        return true;
    }

    /**
     * Determine whether the user can create plants.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the ReceivingGood.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, ReceivingGood $receiving_good)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the ReceivingGood.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, ReceivingGood $receiving_good)
    {
        return self::deleteRules($receiving_good);
    }

    /**
     * Determine whether the user can restore the ReceivingGood.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function restore(User $user, ReceivingGood $receiving_good)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the ReceivingGood.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function forceDelete(User $user, ReceivingGood $receiving_good)
    {
        return false;
    }

    public function responsePolicy($model, $model_2 = null)
    {
        if ($model->hasAnyRole(
            'administrator',
            'super-admin',
            'marketing staff',
            'Marketing Support',
            'Regional Marketing (RM)',
            'Regional Marketing Coordinator (RMC)',
            'Marketing District Manager (MDM)',
            'Assistant MDM',
            'Marketing Manager (MM)',
            'Sales Counter (SC)',
            'Operational Manager',
            'Support Bagian Distributor',
            'Support Distributor',
            'Support Bagian Kegiatan',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
            'User Jember'
        )) {
            return true;
        } else {
            $error = $this->response('05', 'unauthorized action', null);
            throw new HttpResponseException($error);
        }
    }

    /**
     * receiving good has received can not be deleted
     *
     * @param ReceivingGood $receiving_good
     * @return boolean
     */
    public static function deleteRules(ReceivingGood $receiving_good): bool
    {
        return $receiving_good->delivery_status == 2 ? false : true;
    }
}
