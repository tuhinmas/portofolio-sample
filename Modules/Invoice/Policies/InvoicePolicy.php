<?php

namespace Modules\Invoice\Policies;

use App\Traits\ResponseHandler;
use Modules\Invoice\Entities\Invoice;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;

class InvoicePolicy
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
     * Determine whether the user can view the Invoice.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, Invoice $Invoice)
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
     * Determine whether the user can update the Invoice.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, Invoice $Invoice)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the Invoice.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, Invoice $Invoice)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore the Invoice.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\Invoice  $Invoice
     * @return mixed
     */
    public function restore(User $user, Invoice $Invoice)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete the Invoice.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\Invoice  $Invoice
     * @return mixed
     */
    public function forceDelete(User $user, Invoice $Invoice)
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
