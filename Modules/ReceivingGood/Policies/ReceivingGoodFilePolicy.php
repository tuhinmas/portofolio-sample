<?php

namespace Modules\ReceivingGood\Policies;

use App\Traits\ResponseHandler;
use Orion\Concerns\DisableAuthorization;
use Modules\Authentication\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\ReceivingGood\Entities\ReceivingGoodFile;

class ReceivingGoodFilePolicy
{
    use HandlesAuthorization;
    use ResponseHandler;
    use DisableAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, ReceivingGoodFile $receiving_good_file)
    {
        return $this->responsePolicy($user);
    }

    public function create(User $user)
    {
        return $this->responsePolicy($user);
    }

    public function update(User $user, ReceivingGoodFile $receiving_good_file)
    {
        return $this->responsePolicy($user);
    }

    public function delete(User $user, ReceivingGoodFile $receiving_good_file)
    {
        return $this->responsePolicy($user);
    }

    public function restore(User $user, ReceivingGoodFile $receiving_good_file)
    {
        return $this->responsePolicy($user);
    }

    public function forceDelete(User $user, ReceivingGoodFile $receiving_good_file)
    {
        return $this->responsePolicy($user);
    }

    public function responsePolicy($model)
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
            $error = $this->response('05', 'unauthorized action', "you do not have permission to this action");
            throw new HttpResponseException($error);
        }
    }
}
