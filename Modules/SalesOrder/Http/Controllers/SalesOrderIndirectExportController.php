<?php

namespace Modules\SalesOrder\Http\Controllers;

use Carbon\Carbon;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\ExportRequests\Transformers\ExportRequestCollectionResource;
use Modules\SalesOrder\Entities\ExportIndirect;
use Modules\SalesOrder\Transformers\ExportIndirectResource;

class SalesOrderIndirectExportController extends Controller
{
    use DisableAuthorization;

    protected $model = ExportIndirect::class;
    protected $resource = ExportIndirectResource::class;
    protected $collectionResource = ExportRequestCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "order_number",
            "nota",
            "status",
            "order_date",
            "tgl_nota",
            "buyer",
            "seller",
            "marketing",
            "total",
            "catatan"
        ];
    }

    public function includes(): array
    {
        return [
            "exportIndirectChild"
        ];
    }

    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
            "order_number",
            "nota",
            "status",
            "order_date",
            "tgl_nota",
            "buyer",
            "seller",
            "marketing",
            "total",
            "catatan"
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
            "order_number",
            "nota",
            "status",
            "order_date",
            "tgl_nota",
            "buyer",
            "seller",
            "marketing",
            "total",
            "catatan"
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
            "order_number",
            "nota",
            "status",
            "order_date",
            "tgl_nota",
            "buyer",
            "seller",
            "marketing",
            "total",
            "catatan"
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
            "order_number",
            "nota",
            "status",
            "order_date",
            "tgl_nota",
            "buyer",
            "seller",
            "marketing",
            "total",
            "catatan"
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
            return $query->get();
        }

        return $query->paginate($paginationLimit);
    }

    public function indirectSalesExport(Request $request)
    {
        ini_set('max_execution_time', 500);

        $data_fix = ExportIndirect::select("position","tgl_nota","marketing","total","order_number")
            ->when($request->has("year"),function($query) use ($request){
                return $query->whereYear("tgl_nota",$request->year);
            })
            ->orderBy("tgl_nota","desc")->get()->groupBy([
            function ($val) {
                return Carbon::parse($val->tgl_nota)->format('m-Y');
            },
            function ($val) {
                return $val->marketing;
            }
        ])->map(function($monthYear) {
            return $monthYear->map(function($value, $marketing) {
                $banyak_co = 0;
                $nominal = 0;
                $tahun = Carbon::parse($value->first()->tgl_nota)->format("Y");
                $bulan = Carbon::parse($value->first()->tgl_nota)->format("F");
                $position = $value->first()->position;
                $data_per = $value->first()->tgl_nota;
                
                foreach($value as $data){
                    $nominal += $data->total;
                }
                return [
                    "marketing" => $marketing,
                    "tahun" => $tahun,
                    "bulan" => $bulan,
                    "position" => $position,
                    "data_per" => $data_per,
                    "nominal" => $value->sum('total'),
                    "banyak_co" => count($value),
                ];
            });
        })->flatten(1)->values();
    

        return response()->json([
            'response_code' => '00',
            'response_message' => 'export indirect sales',
            'data' => collect($data_fix)->values(),
        ]);
    }
}
