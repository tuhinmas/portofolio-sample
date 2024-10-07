<?php

namespace Modules\ReceivingGood\Policies;

use App\Traits\ResponseHandler;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;

class ReceivingGoodDetailPolicy
{
    use HandlesAuthorization;
    use ResponseHandler;
    use DisableAuthorization;

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
     * Determine whether the user can view the ReceivingGoodDetail.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, ReceivingGoodDetail $ReceivingGoodDetail)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can create ReceivingGoodDetail.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can update the ReceivingGoodDetail.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, ReceivingGoodDetail $ReceivingGoodDetail)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the ReceivingGoodDetail.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, ReceivingGoodDetail $ReceivingGoodDetail)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore the ReceivingGood.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\ReceivingGood  $ReceivingGood
     * @return mixed
     */
    public function restore(User $user, ReceivingGoodDetail $ReceivingGoodDetail)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete the ReceivingGoodDetail.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\ReceivingGoodDetail  $ReceivingGoodDetail
     * @return mixed
     */
    public function forceDelete(User $user, ReceivingGoodDetail $ReceivingGoodDetail)
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
            'User Jember',
            "support"
        )) {
            return true;
        } else {
            $error = $this->response('05', 'unauthorized action', null);
            throw new HttpResponseException($error);
        }
    }
}
