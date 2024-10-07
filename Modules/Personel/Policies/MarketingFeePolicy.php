<?php

namespace Modules\Personel\Policies;

use App\Traits\ResponseHandler;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\MarketingFee;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;

class MarketingFeePolicy
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
     * Determine whether the user can view thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, MarketingFee $marketing_fee)
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
     * Determine whether the user can update thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, MarketingFee $marketing_fee)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, MarketingFee $marketing_fee)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\  $marketing_fee
     * @return mixed
     */
    public function restore(User $user, MarketingFee $marketing_fee)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\  $marketing_fee
     * @return mixed
     */
    public function forceDelete(User $user, MarketingFee $marketing_fee)
    {
        return $this->responsePolicy($user);
    }

    public function syncFeeMarketing(User $user, MarketingFee $point_marketing)
    {
        if (auth()->user()->hasAnyPermission([
            "Sinkronisasi Fee Marketing",
        ])) {
            return true;
        }
        $error = $this->response('05', 'unauthorized action', "You do not have permission to this action", 403);
        throw new HttpResponseException($error);
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
            $error = $this->response('05', 'unauthorized action', "You have no permission to this action");
            throw new HttpResponseException($error);
        }
    }
}
