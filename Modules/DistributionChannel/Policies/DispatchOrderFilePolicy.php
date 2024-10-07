<?php

namespace Modules\DistributionChannel\Policies;

use App\Traits\ResponseHandler;
use App\Traits\RoleAccessModel;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DistributionChannel\Entities\DispatchOrderFile;

class DispatchOrderFilePolicy
{
    use HandlesAuthorization, ResponseHandler, RoleAccessModel;
    use DisableAuthorization;
    
    /**
     * index policy
     *
     * @param User $user
     * @return void
     */
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, DispatchOrderFile $deliveryOrder)
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
     * Determine whether the user can update the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, DispatchOrderFile $deliveryOrder)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, DispatchOrderFile $deliveryOrder)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\SalesOrderV2  $sales_Order
     * @return mixed
     */
    public function restore(User $user, DispatchOrderFile $deliveryOrder)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\SalesOrderV2  $sales_Order
     * @return mixed
     */
    public function forceDelete(User $user, DispatchOrderFile $deliveryOrder)
    {
        return $this->responsePolicy($user);
    }

    /**
     * roles check
     *
     * @param [type] $model
     * @param [type] $model_2
     * @return void
     */
    public function responsePolicy($model, $model_2 = null)
    {
        if ($model->hasAnyRole($this->roleListToAccesssModel("DispatchOrderFile"))) {
            return true;
        } else {
            $error = $this->response('05', 'unauthorized action', null);
            throw new HttpResponseException($error);
        }
    }

}