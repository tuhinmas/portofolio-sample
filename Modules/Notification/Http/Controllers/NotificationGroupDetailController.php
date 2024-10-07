<?php

namespace Modules\Notification\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\Notification\Entities\NotificationGroupDetail;
use Modules\Notification\Http\Requests\NotificationGroupDetailRequest;
use Modules\Notification\Transformers\NotificationGroupDetailResource;
use Modules\Notification\Transformers\NotificationGroupDetailCollectionResource;

class NotificationGroupDetailController extends Controller
{
    use DisableAuthorization;

    protected $model = NotificationGroupDetail::class;
    protected $request = NotificationGroupDetailRequest::class;
    protected $resource = NotificationGroupDetailResource::class;
    protected $collectionResource = NotificationGroupDetailCollectionResource::class;

    public function performStore(Request $request, Model $entity, array $attributes) : void{
        $attributes["condition"] = json_encode($attributes["condition"]);
        $entity->fill($attributes);
        $entity->save();
    }
}
