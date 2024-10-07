<?php

namespace Modules\KiosDealer\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\KiosDealer\Transformers\DealerTempResource;
use Modules\KiosDealer\Http\Requests\DealerTempFileRequest;
use Modules\KiosDealer\Transformers\DealerTempFileResource;
use Modules\KiosDealer\Transformers\DealerTempFileCollectionResource;

class DealerTempFileController extends Controller
{     
    use DisableAuthorization;

    protected $model = DealerFileTemp::class;
    protected $request = DealerTempFileRequest::class;
    protected $resource = DealerTempFileResource::class;
    protected $collectionResource = DealerTempFileCollectionResource::class;

    public function alwaysIncludes() : array
    {
        return ["dealer"];
    }

    public function filterableBy() : array
    {
        return [
            "id", 
            "dealer_id", 
            "file_type", 
            "created_at", 
            "updated_at"
        ];
    }
}
