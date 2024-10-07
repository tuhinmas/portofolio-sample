<?php

namespace App\Traits;

use Modules\Personel\Entities\Personel;

/**
 *
 */
trait SupervisorRoleTrait
{
    public function showAllDataForUser()
    {
        if (auth()->user()->hasAnyRole(
            'administrator',
            'super-admin',
            'Marketing Support',
            'Marketing Manager (MM)',
            'Sales Counter (SC)',
            'Operational Manager',
            'Support Bagian Distributor',
            'Support Distributor',
            'Support Bagian Kegiatan',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
        )) {
            return true;
        }
        return false;
    }

    public function personelRoleCheckForAllData($personel_id)
    {
        $personel = Personel::query()
            ->with([
                "user",
            ])
            ->findOrFail($personel_id);

        if ($personel->user) {
            if ($personel->user->roles) {
                foreach ($personel->user->roles as $role) {
                    if (in_array($role->name, [
                        'administrator',
                        'super-admin',
                        'Marketing Support',
                        'Marketing Manager (MM)',
                        'Sales Counter (SC)',
                        'Operational Manager',
                        'Support Bagian Distributor',
                        'Support Distributor',
                        'Support Bagian Kegiatan',
                        'Support Kegiatan',
                        'Support Supervisor',
                        'Distribution Channel (DC)',
                    ])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
