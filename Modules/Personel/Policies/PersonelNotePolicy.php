<?php

namespace Modules\Personel\Policies;

use App\Traits\ResponseHandler;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\PersonelNote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonelNotePolicy
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
     * Determine whether the user can view thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function view(User $user, PersonelNote $personel_note)
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
    public function update(User $user, PersonelNote $personel_note)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can delete thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, PersonelNote $personel_note)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can restore thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\  $personel_note
     * @return mixed
     */
    public function restore(User $user, PersonelNote $personel_note)
    {
        return $this->responsePolicy($user);
    }

    /**
     * Determine whether the user can permanently delete thePersonelNote.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\  $personel_note
     * @return mixed
     */
    public function forceDelete(User $user, PersonelNote $personel_note)
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
            $error = $this->response('05', 'unauthorized action', "You have no permission to this action");
            throw new HttpResponseException($error);
        }
    }
}
