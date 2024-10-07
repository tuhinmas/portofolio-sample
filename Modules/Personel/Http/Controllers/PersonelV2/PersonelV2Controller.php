<?php

namespace Modules\Personel\Http\Controllers\PersonelV2;

use Orion\Http\Controllers\Controller;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Modules\Personel\Transformers\MarketingResource;
use Modules\Personel\Http\Requests\PersonelV2Request;
use Modules\Personel\Transformers\MarketingCollectionResource;

class PersonelV2Controller extends Controller
{
    use DisableAuthorization;
    
    protected $model = Personel::class;
    protected $request = PersonelV2Request::class;
    protected $resource = MarketingResource::class;
    protected $collectionResource = MarketingCollectionResource::class;

    public function includes(): array
    {
        return ["*"];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterAbleBy(): array
    {
        return [];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchAbleBy(): array
    {
        return [];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortAbleBy(): array
    {
        return [];
    }
}
