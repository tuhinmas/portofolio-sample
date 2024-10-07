<?php

namespace Modules\Invoice\Http\Controllers;

use Storage;
use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Entities\InvoiceProforma;
use Modules\Invoice\Http\Requests\InvoiceProformaRequest;
use Modules\Invoice\Transformers\InvoiceProformaResource;
use Modules\Invoice\Transformers\InvoiceProformaCollectionResource;

class InvoiceProformaController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = InvoiceProforma::class;
    protected $request = InvoiceProformaRequest::class;
    protected $resource = InvoiceProformaResource::class;
    protected $collectionResource = InvoiceProformaCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [

        ];
    }

    /**
     * include data relation
     */
    public function includes(): array
    {
        return [
            "personel",
            "personel.position",
            "invoice",
            "invoice.payment",
            "invoice.dispatchOrder",
            "invoice.dispatchOrder.deliveryOrder",
            "invoice.dispatchOrder.warehouse",
            "invoice.salesOrder",
            "invoice.salesOrder.personel",
            "invoice.salesOrder.personel.position",
            "invoice.salesOrder.dealer",
            "invoice.salesOrder.dealer.personel",
            "invoice.salesOrder.dealer.personel.position",
            "invoice.salesOrder.dealer.personel",
            "invoice.salesOrder.dealer.adress_detail.province",
            "invoice.salesOrder.dealer.adress_detail.city",
            "invoice.salesOrder.dealer.adress_detail.district",
            "invoice.salesOrder.sales_order_detail",
            "invoice.salesOrder.sales_order_detail.product",
            "invoice.salesOrder.sales_order_detail.product.package",
            "invoice.salesOrder.sales_order_detail",
            "invoice.salesOrder.paymentMethod",
            "confirmedBy",
            "receipt",
            "receipt.confirmedBy",
            "receipt.confirmedBy.position",
        ];
    }

    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [

        ];
    }


        /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {

        if ($request->has("disabled_pagination")) {
           return $query->orderBy('created_at', 'DESC')->get();
        } else {
            return $query->orderBy('created_at', 'DESC')->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "id",
            "personel_id",
            "invoice_id",
            "invoice_proforma_number",
            "link",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            "id",
            "personel_id",
            "invoice_id",
            "invoice_proforma_number",
            "link",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "id",
            "personel_id",
            "invoice_id",
            "invoice_proforma_number",
            "link",
            "created_at",
            "updated_at",
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
        $personel_id = auth()->user()->personel_id;

        if ($personel_id) {
            $personel_id = DB::table('personels')->whereNull("deleted_at")->where("name", "kantor")->first()->id;
        }
        $char = "JAY-";
        $invoice_proforma = DB::table('invoice_proformas')->select("proforma_number")->whereNull("deleted_at")->orderBy("proforma_number","desc")->latest()->first();
        
        /* get receipt for proforma */
        $receipt_proforma = DB::table('proforma_receipts')->whereNull("deleted_at")->where("receipt_for", "2")->orderBy("created_at", "desc")->first();

        /* get current operational manager */
        $operational_manager = Personel::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->where("name", "Operational Manager");
            })
            ->first();

        $attributes["confirmed_by"] = $operational_manager ? $operational_manager->id : null;
        $attributes["receipt_id"] = $receipt_proforma ? $receipt_proforma->id : null;

        $proforma_number = ($invoice_proforma !== null) ? $invoice_proforma->proforma_number : 0;
        if($request->has("invoice_proforma_number")) {
            $entity["invoice_proforma_number"]  = $attributes['invoice_proforma_number'];
        } else { 
            $entity["invoice_proforma_number"]  = $char .str_pad($proforma_number+1,6,0, STR_PAD_LEFT);
        }
        $entity["proforma_number"] = $proforma_number+1;
        $entity["issued_by"] = $personel_id;
        $entity->fill($attributes);
        $entity->save();
    }

    /**
     * upload attachemnt
     *
     * @param [type] $request
     * @return void
     */
    public function uploadFile($request, $status = null)
    {
        $success = false;
        try {
            $file_extension = $request->file->getClientOriginalExtension();
            $file_name = $request->invoice_id . "-" . $request->invoice_proforma_number . "." . $file_extension;
            $s3_path = Storage::disk('s3')->url('public/nota/invoice/');
            $data = null;

            /* check update or not */
            if ($status == "update") {
                $path = $request->file('file')->storeAs('public/nota/invoice', $file_name);
                if ($path) {
                    $success = true;
                }
                $data = (object) [
                    "status" => $success,
                    "data" => $s3_path . $file_name,
                    "message" => "upload success",
                ];
            } else {

                /* on store check data exist */
                if (Storage::disk('s3')->exists('public/nota/invoice/' . $file_name)) {
                    $data = (object) [
                        "status" => $success,
                        "data" => $s3_path . $file_name,
                        "message" => "file already exist",
                    ];
                    return $data;
                } else {
                    $path = $request->file('file')->storeAs('public/nota/invoice', $file_name);
                    if ($path) {
                        $success = true;
                    }
                    $data = (object) [
                        "status" => $success,
                        "data" => $s3_path . $file_name,
                        "message" => "upload success",
                    ];
                }
            }
            return $data;
        } catch (\Throwable$th) {
            return $th->getMessage();
        }
    }

}
