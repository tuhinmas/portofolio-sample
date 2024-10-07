<?php

namespace Modules\Authentication\Http\Controllers;

use Modules\Authentication\Entities\Device;
use Modules\Authentication\Transformers\DeviceCollectionResource;
use Modules\Authentication\Transformers\DeviceResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class DeviceController extends Controller
{
    use DisableAuthorization;

    protected $model = Device::class;
    protected $resource = DeviceResource::class;
    protected $collectionResource = DeviceCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "personel"
        ];
    }

    public function includes(): array
    {
        return [
            "personel"
        ];
    }

    /**
     * filter list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "user.personel_id",
            "manufacture",
            "version_app",
            "personel.id",
            "created_at",
            "updated_at",
            "version_os",
            "device_id",
            "longitude",
            "is_mobile",
            "latitude",
            "user_id",
            "model",
        ];
    }

    /**
     * sort list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "personel.name",
            "manufacture",
            "version_app",
            "created_at",
            "updated_at",
            "version_os",
            "device_id",
            "longitude",
            "is_mobile",
            "latitude",
            "user_id",
            "model",
        ];
    }
}
