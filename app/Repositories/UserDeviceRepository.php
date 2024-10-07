<?php

namespace App\Repositories;

use App\Models\UserDevice;
use Exception;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class RegisterDeviceRepository
 */
class UserDeviceRepository
{

    /**
     * @param $input
     *
     * @return UserDevice
     */
    public function updateOrCreate($input)
    {
        try {
            return UserDevice::updateOrCreate([
                'os_player_id' => $input['os_player_id'],
            ], [
                'user_id' => array_key_exists("user_id", $input) ? $input["user_id"] : auth()->user()->id,
                'device_type' => $input['device_type'],
            ]);
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $playerId
     *
     * @return bool
     */
    public function updateStatus($playerId): bool
    {
        $userDevice = UserDevice::whereOsPlayerId($playerId)->first();
        $userDevice->update(['is_active' => !$userDevice->is_active]);

        return true;
    }
}
