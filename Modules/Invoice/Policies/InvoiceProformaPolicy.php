<?php

namespace Modules\Invoice\Policies;

use App\Traits\ResponseHandler;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Entities\InvoiceProforma;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;

class InvoiceProformaPolicy
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
     * Determine whether the user can view the InvoiceProforma.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, InvoiceProforma $invoice_proforma)
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
     * Determine whether the user can update the InvoiceProforma.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, InvoiceProforma $invoice_proforma)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete the InvoiceProforma.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, InvoiceProforma $invoice_proforma)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore the InvoiceProforma.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\InvoiceProforma  $invoice_proforma
     * @return mixed
     */
    public function restore(User $user, InvoiceProforma $invoice_proforma)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete the InvoiceProforma.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\InvoiceProforma  $invoice_proforma
     * @return mixed
     */
    public function forceDelete(User $user, InvoiceProforma $invoice_proforma)
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
            $error = $this->response('05', 'unauthorized action', "You do not have pemission to this action");
            throw new HttpResponseException($error);
        }
    }
}
