<?php

namespace Modules\ReceivingGood\Http\Controllers;

use Modules\ReceivingGood\Entities\ReceivingGoodFile;
use Modules\ReceivingGood\Http\Requests\ReceivingGoodFileRequest;
use Modules\ReceivingGood\Transformers\ReceivingGoodFileCollectionResource;
use Modules\ReceivingGood\Transformers\ReceivingGoodFileResource;
use Orion\Http\Controllers\Controller;

class ReceivingGoodFileController extends Controller
{
    protected $model = ReceivingGoodFile::class;
    protected $request = ReceivingGoodFileRequest::class;
    protected $resource = ReceivingGoodFileResource::class;
    protected $collectionResource = ReceivingGoodFileCollectionResource::class;

    public function includes(): array
    {
        return [
            "receivingGood"
        ];
    }

    /**
     * filter
     */
    public function filterAbleBy(): array
    {
        return [
            "receiving_good_id",
            "attachment_status",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * search
     */
    public function searchAbleBy(): array
    {
        return [
            "receiving_good_id",
            "attachment_status",
        ];
    }

    /**
     * sort
     */
    public function sortAbleBy(): array
    {
        return [
            "receiving_good_id",
            "attachment_status",
            "created_at",
            "updated_at",
        ];
    }
}
