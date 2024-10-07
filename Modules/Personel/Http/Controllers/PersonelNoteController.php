<?php

namespace Modules\Personel\Http\Controllers;

use Modules\Personel\Entities\PersonelNote;
use Modules\Personel\Http\Requests\PersonelNoteRequest;
use Modules\Personel\Transformers\PersonelNoteCollectionResource;
use Modules\Personel\Transformers\PersonelNoteResource;
use Orion\Http\Controllers\Controller;

class PersonelNoteController extends Controller
{
    protected $model = PersonelNote::class;
    protected $request = PersonelNoteRequest::class;
    protected $resource = PersonelNoteResource::class;
    protected $collectionResource = PersonelNoteCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "user",
            "personel",
        ];
    }

    public function includes(): array
    {
        return [

        ];
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
        return [
            "id",
            "user_id",
            "personel_id",
            "type",
            "status",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchAbleBy(): array
    {
        return [
            "user_id",
            "personel_id",
            "type",
            "status",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortAbleBy(): array
    {
        return [
            "user_id",
            "personel_id",
            "type",
            "status",
            "created_at",
            "updated_at",
        ];
    }
}
