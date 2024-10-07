<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\SalesOrder\Entities\ExportConfirmedSale;
use Modules\SalesOrder\Entities\ExportConfirmedSaleDetail;
use Modules\SalesOrder\Http\Requests\ExportConfirmedSaleRequest;
use Modules\SalesOrder\Transformers\ExportConfirmedSaleResource;
use Modules\SalesOrder\Transformers\ExportConfirmedSaleCollectionResource;

class ExportConfirmedSaleController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = ExportConfirmedSale::class;
    protected $request = ExportConfirmedSaleRequest::class;
    protected $resource = ExportConfirmedSaleResource::class;
    protected $collectionResource = ExportConfirmedSaleCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [

        ];
    }

    public function includes(): array
    {
        return [
            "salesOrderDetail",
            "statusFee"
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
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "transaction_date",
            "payment_status",
            "last_payment",
            "sales_type",
            'created_at',
            'updated_at',
            "marketing",
            "sub_total",
            "sales_id",
            "discount",
            "invoice",
            "nominal",
            "payment",
            "status",
            "seller",
            "buyer",
            "total",
            "ppn",
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
            "transaction_date",
            "payment_status",
            "last_payment",
            "sales_type",
            'created_at',
            'updated_at',
            "marketing",
            "sub_total",
            "sales_id",
            "discount",
            "invoice",
            "nominal",
            "payment",
            "status",
            "seller",
            "buyer",
            "total",
            "ppn",
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
            "transaction_date",
            "payment_status",
            "last_payment",
            "sales_type",
            'created_at',
            'updated_at',
            "marketing",
            "sub_total",
            "sales_id",
            "discount",
            "invoice",
            "nominal",
            "payment",
            "status",
            "seller",
            "buyer",
            "total",
            "ppn",
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
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->disabled_pagination) {
            return $query
            ->withSum("salesOrderDetail","quantity")->with("statusFee")->withSum("salesOrderDetail","quantity_order")->get();
        }

        return $query->paginate($paginationLimit);
    }

    public function exportShopTransaction(Request $request)
    {
        ini_set('max_execution_time', 1500);
        
        $shop = ExportConfirmedSaleDetail::when($request->has("store_id"),function($query) use ($request){
            return $query->where("store_id",$request->store_id);
        })->with("statusFee","distributor","counter","marketing","counter","grading","agencyLevel","salesOrderConfirmed","salesOrder")->get()
            ->map(function ($item, $k) {
                return (Object) [
                    "order_number" => $item->order_number,
                    "kategori" => $item->type == "1" ? "Direct" : "Indirect",
                    "status_order" => $item->salesOrder ? $item->salesOrder->status : "-",
                    "status_limpahan" => $item->statusFee ? $item->statusFee->name : "-",
                    "no_inv" => $item->inv,
                    "tgl_transaksi" => $item->transaction_date,
                    "tgl_lunas" => $item->date_settle,
                    "toko_id" => $item->store,
                    "depo" => $item->distributor ? "CUST-".$item->distributor->dealer_id : "Javamas",
                    "marketing" => $item->marketing ? $item->marketing->name : "-",
                    "follow_up_days" => $item->follow_up_days,
                    "sales_counter" => $item->counter ? $item->counter->name : "-",
                    "produk_size" => $item->product,
                    "qty_order" => $item->quantity_order,
                    "qty_beli" => $item->quantity,
                    "unit_price" => $item->unit_price,
                    "sub_total" => $item->salesOrderConfirmed ? $item->salesOrderConfirmed->sub_total : "0.00",
                    "diskon" => $item->discount,
                    "grade_beli" => $item->grading ? $item->grading->name : "-",
                    "agency_beli" => $item->agencyLevel ? $item->agencyLevel->name : "-",
    
                ];
            });

            return $this->response('00', 'success, View Transaction Shop', $shop);
   
    }
}
