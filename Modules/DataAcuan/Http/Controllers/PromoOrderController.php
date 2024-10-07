<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Event\Entities\Event;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\Promo;
use Modules\DataAcuan\Http\Requests\PromoRequest;
use Modules\DataAcuan\Repositories\PromoProductRepository;
use Modules\DataAcuan\Transformers\PromoResource;
use Modules\DataAcuan\Transformers\PromoOrderCollectionResource;
use Modules\DataAcuan\Transformers\PromoOrderResource;

class PromoOrderController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = Promo::class; 
    protected $request = PromoRequest::class;
    protected $resource = PromoOrderResource::class;
    protected $collectionResource = PromoOrderCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [];
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
    public function filterableBy(): array
    {
        return [
            "id",
            "name",
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
            "name",
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
            "date_start",
            "date_end",
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
        return $query->orderBy('date_start', 'desc');
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
        $query->with(['createdBy','createdBy.position'])->whereNotNull('attributes');

        if($request->has('promo_date')){
            $query->where('date_end','>=', date('Y-m-d', strtotime($request->promo_date)))->where('date_start','<=', date('Y-m-d', strtotime($request->promo_date)));
        }elseif ($request->has('promo_status')) {
            switch ($request->promo_status) {
                case 0:
                    $query->where('date_end','>=', date('Y-m-d'))->where('date_start','<=', date('Y-m-d'));
                    break;

                case 1:
                    $query->where('date_end','<=', date('Y-m-d'))->where('date_start','>=', date('Y-m-d'));
                        break;
                
                default:
                    break;
            }
        }

        
        if ($request->has("disable_pagination") && $request->disable_pagination == true) {
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15)->through(function ($promo) {
                $data = json_decode($promo->attributes, true);
                $promo->attributes = $data ?? [];
                $promo->count_product =  count($data ?? []);
                $promo->created_by = optional($promo->createdBy)->name;
                $promo->position_name = !empty(optional($promo->createdBy)->position) ? optional($promo->createdBy)->position->name : null;
                return $promo;
            });
        }
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
        $entity->created_by = auth()->user()->personel_id;
        $entity->save();
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
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        return $query;
    }
    
    public function simple(Request $request)
    {
        try {
            $promoProduct = new PromoProductRepository();
            $response = $promoProduct->listSimplePromo($request->all());
            return $this->response("00", "Product Available", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save", $th->getMessage(), 500);
        }
    }
    
}
