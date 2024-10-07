<?php

namespace Modules\DataAcuan\Policies;

use App\Traits\ResponseHandler;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\PlantCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlantCategoryPolicy
{
    use HandlesAuthorization;
    use ResponseHandler;
    use DisableAuthorization;

    /**
     * Determine whether the user can view the list of plant_categorys.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the plant_category.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\PlantCategory  $plant_category
     * @return mixed
     */
    public function view(User $user, PlantCategory $plant_category)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can create plant_categorys.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can update the plant_category.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\PlantCategory  $plant_category
     * @return mixed
     */
    public function update(User $user, PlantCategory $plant_category)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the plant_category.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\PlantCategory  $plant_category
     * @return mixed
     */
    public function delete(User $user, PlantCategory $plant_category)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore the plant_category.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\PlantCategory  $plant_category
     * @return mixed
     */
    public function restore(User $user, PlantCategory $plant_category)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete the plant_category.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\PlantCategory  $plant_category
     * @return mixed
     */
    public function forceDelete(User $user, PlantCategory $plant_category)
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
