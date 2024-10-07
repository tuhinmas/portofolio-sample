<?php

namespace Modules\DataAcuan\Http\Controllers;

use Carbon\Carbon;
use App\Traits\ChildrenList;
use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\GradingBlock;
use Modules\DataAcuan\Entities\GradingBlokir;
use Modules\DataAcuan\Events\GradingBlockDeleteDealerEvent;
use Modules\DataAcuan\Http\Requests\GradingBlockRequest;
use Modules\DataAcuan\Http\Requests\GradingRequest;
use Modules\DataAcuan\Transformers\GradingBlockCollectionResource;
use Modules\DataAcuan\Transformers\GradingBlockResource;
use Modules\KiosDealerV2\Entities\DealerV2;

class GradingBlockController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;
    use ChildrenList;

    protected $model = GradingBlock::class;
    protected $request = GradingBlockRequest::class;
    protected $resource = GradingBlockResource::class;
    protected $collectionResource = GradingBlockCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "grading",
            "personel",
            "personel.position"
        ];
    }

    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "is_active",
            "grading_id",
            "personel_id",
            "personel.name",
            "grading.name",
            "created_at",
            "deleted_at"
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
            "is_active",
            "grading_id",
            "personel_id",
            "personel.name",
            "grading.name",
            "created_at",
            "deleted_at"
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
        return parent::buildIndexFetchQuery($request, $requestedRelations);
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
            return $query->withTrashed()->get();
        } else {
            return $query->withTrashed()
                 ->orderByRaw("DATE_FORMAT(grading_blocks.created_at, '%Y-%m-%d') DESC")
                 ->orderByRaw("DATE_FORMAT(grading_blocks.deleted_at, '%Y-%m-%d') ASC")
                ->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function beforeStore(Request $request, $model)
    {
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterStore(Request $request, $model)
    {
        // rule deleted all dealer with grading_id == request->grading_id
        GradingBlockDeleteDealerEvent::dispatch($model);
    }

    public function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildShowFetchQuery($request, $requestedRelations);
        return $query;
    }


    public function beforeDestroy(Request $request, $model)
    {
        $GradingBlock = GradingBlock::find($model->id);
        $GradingBlock->is_active = false;
        $GradingBlock->save();

        $validate = Validator::make($request->all(), [
            "personel_id" => "required"
        ]);

        if ($validate->fails()) {
            return $this->response("04", "invalid data send", $validate->errors());
        }
    }

    public function afterDestroy(Request $request, $model)
    {
        // if deleted then re-active dealer where grading_id = grading_block_id

        DealerV2::where("grading_id", $model->grading_id)
            ->where("is_block_grading", true)->update(
                [
                    // 'deleted_at' => null,
                    'blocked_by' => null,
                    'blocked_at' => null,
                    'grading_block_id' => null,
                    'is_block_grading' => false
                ]
            );
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations)->withTrashed();
        return $query;
    }


    public function afterUpdate(Request $request, $model)
    {
        $GradingBlock = GradingBlock::find($model->id);
        $GradingBlock->is_active = true;
        $GradingBlock->save();
        // if deleted_at remove, then destroy/block dealer again
        DealerV2::where("grading_id", $model->grading_id)->where("is_block_grading", true)->update(
            [
                // 'deleted_at' => Carbon::now(),
                'blocked_by' => Auth::user()->personel_id,
                'blocked_at' => Carbon::now()->format("Y-m-d"),
                'grading_block_id' => $model->grading_id,
                'is_block_grading' => true
            ]
        );
    }
}
