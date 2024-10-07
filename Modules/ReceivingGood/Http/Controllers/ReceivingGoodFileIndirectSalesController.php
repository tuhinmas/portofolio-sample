<?php

namespace Modules\ReceivingGood\Http\Controllers;

use App\Actions\AwsFileUploaderAction;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectFile;
use Modules\ReceivingGood\Http\Requests\ReceivingGoodFileIndirectSalesRequest;
use Modules\ReceivingGood\Transformers\ReceivingGoodFileIndirectSalesCollectionResource;
use Modules\ReceivingGood\Transformers\ReceivingGoodFileIndirectSalesResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class ReceivingGoodFileIndirectSalesController extends Controller
{

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     *
     */
    use ResponseHandler;
    use DisableAuthorization;
    use MarketingArea;

    protected $model = ReceivingGoodIndirectFile::class;
    protected $request = ReceivingGoodFileIndirectSalesRequest::class;
    protected $resource = ReceivingGoodFileIndirectSalesResource::class;
    protected $collectionResource = ReceivingGoodFileIndirectSalesCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    public function includes(): array
    {
        return [
            "receivingGoodIndirect",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'receiving_good_id',
            'attachment',
            'attachment_status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'receiving_good_id',
            'attachment',
            'attachment_status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'sales_order_id',
            'delivery_number',
            'status',
            'note',
            'date_received',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [

        ];
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        unset($attributes["file"]);
        $entity->fill($attributes);
        $entity->save();

        if ($request->has("file")) {
            $path = (new AwsFileUploaderAction)($request->file("file"), $entity->id, "public/indirect/receiving-good/attachment/", "s3");

            if ($path["is_exist"]) {
                $entity->attachment = $path["file_name"];
                $entity->save();

            } else {
                $entity->delete();
                $resposne = $this->response("01", "failed", [
                    "message" => [
                        "file gagal diupload",
                    ],
                ], 422);
                throw new HttpResponseException($resposne);
            }
        }
    }
}
