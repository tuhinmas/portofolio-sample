<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Orion\Http\Controllers\Controller;
use Modules\DataAcuan\Entities\Grading;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\GradingBlock;
use Modules\DataAcuan\Http\Requests\GradingRequest;
use Modules\DataAcuan\Transformers\GradingResource;
use Modules\DataAcuan\Transformers\GradingCollectionResource;
use Orion\Http\Requests\Request as RequestsRequest;

class GradingController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = Grading::class;
    protected $request = GradingRequest::class;
    protected $resource = GradingResource::class;
    protected $collectionResource = GradingCollectionResource::class;


    /**
     * filetr list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "name",
            "description"
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
            "name",
            "description",
            "max_unsettle_proformas",
            "credit_limit",
            "maximum_payment_days",
            "bg_color"
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
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
            return $query
                ->when($request->is_dealer_grading_block == true, function ($query) {
                    $gradingBlockDistinct = GradingBlock::distinct()->get("grading_id")->map(function ($val) {
                        return $val->grading_id;
                    });
                    $query->whereNotIn("id", $gradingBlockDistinct);
                })
                ->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function simpleGrading(Request $request)
    {
        try {
            $gradingBlockDistinct = GradingBlock::distinct()->get("grading_id")->map(function ($val) {
                return $val->grading_id;
            });
            $grading = DB::table('gradings')->select('id', 'name')
                ->whereNotIn("id", $gradingBlockDistinct)
                ->whereNull('deleted_at')
                ->when($request->has('id'), function ($q) use ($request) {
                    return $q->where('id', $request->id);
                })->when($request->has('name'), function ($q) use ($request) {
                    return $q->where('name', 'like', '%' . $request->name . '%');
                });

            if ($request->has('limit')) {
                $response = $grading->paginate($request->limit);
            } else {
                $response = $grading->get();
            }
            return $this->response('00', 'success, get simple grading', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed, get simple grading', $th->getMessage());
        }
    }
}
