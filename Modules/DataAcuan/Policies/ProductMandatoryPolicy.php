<?php

namespace Modules\DataAcuan\Policies;

use App\Traits\ResponseHandler;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\DataAcuan\Entities\ProductMandatory;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductMandatoryPolicy
{
    use HandlesAuthorization, ResponseHandler;
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

    public function view(User $user, ProductMandatory $productMandatory)
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
    public function update(User $user, ProductMandatory $productMandatory)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, ProductMandatory $productMandatory)
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
    public function restore(User $user, ProductMandatory $productMandatory)
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
    public function forceDelete(User $user, ProductMandatory $productMandatory)
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
        if ($model->hasAnyRole(
            'administrator',
            'admin',
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
