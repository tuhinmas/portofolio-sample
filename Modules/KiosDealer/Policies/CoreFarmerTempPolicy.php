<?php

namespace Modules\KiosDealer\Policies;

use App\Traits\ResponseHandler;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\CoreFarmerTemp;

class CoreFarmerTempPolicy
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
     * Determine whether the user can view the CoreFarmerTemp.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, CoreFarmerTemp $core_farmer_temp)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can create plants.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can update the CoreFarmerTemp.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, CoreFarmerTemp $core_farmer_temp)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the CoreFarmerTemp.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, CoreFarmerTemp $core_farmer_temp)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore the CoreFarmerTemp.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\CoreFarmerTemp  $core_farmer_temp
     * @return mixed
     */
    public function restore(User $user, CoreFarmerTemp $core_farmer_temp)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete the CoreFarmerTemp.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\CoreFarmerTemp  $core_farmer_temp
     * @return mixed
     */
    public function forceDelete(User $user, CoreFarmerTemp $core_farmer_temp)
    {
        return $this->responsePolicy($user);
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
}
