<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use Orion\Http\Requests\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Position;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\FeePosition;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Http\Requests\FeePositionRequest;
use Modules\DataAcuan\Transformers\FeePositionResource;
use Modules\DataAcuan\Transformers\FeePositionCollectionResource;

class FeePositionController extends Controller
{
    use DisableAuthorization;
    use ResponseHandlerV2;

    protected $model = FeePosition::class;
    protected $request = FeePositionRequest::class;
    protected $resource = FeePositionResource::class;
    protected $collectionResource = FeePositionCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "feeCashMinimumOrder",
            "position",
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
        return [
        ];
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
            "position_id",
            "fee",
            "follow_up",
            "fee_cash",
            "fee_cash_minimum_order",
            "created_at",
            "updated_at",
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
            "id",
            "position_id",
            "fee",
            "follow_up",
            "fee_cash",
            "fee_cash_minimum_order",
            "created_at",
            "updated_at",
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
            "id",
            "position_id",
            "fee",
            "follow_up",
            "fee_cash",
            "fee_cash_minimum_order",
            "created_at",
            "updated_at",
        ];
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

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function beforeUpdate(Request $request, $model)
    {
        if (isset($request->all()["resources"])) {
            $fee_position = DB::table('fee_positions')
                ->whereNull("deleted_at")
                ->count();

            $response = null;
            if ($fee_position != count(array_unique(array_keys($request->all()["resources"])))) {
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "request body did not match with data reference",
                    ],
                ], 422);
            } elseif (
                collect(array_values($request->all()["resources"]))
                    ->unique("date_start")
                    ->count() > 1
            ) {
                $response = $this->response("04", "invalid data send", [
                    "date_start" => [
                        "all date_start must have same value",
                    ],
                ], 422);
            }

            if ($response) {
                throw new HttpResponseException($response);
            }
        }
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $last_fee_position = $entity->fee;
        $last_fee_follow_up = $entity->follow_up;
        $last_sales_counter_fee_percentage = $entity->fee_sc_on_order;
        $attributes["date_start"] = $request->date_start ?? now();
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterSave(Request $request, $model)
    {
        if (array_key_exists("resources", $request->all())) {
            foreach ($request->resources as $key => $data) {
                Position::findOrFail($data["position_id"]);
                AgencyLevel::findOrFail($data["fee_cash_minimum_order"]);
            }
        } else {
            Position::findOrFail($request->position_id);
            AgencyLevel::findOrFail($request->fee_cash_minimum_order);
        }
    }

    public function personelUpdate($attributes)
    {
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "L1")->first()->id;
        $district_id = $attributes["district_id"];
        $personel_id = $this->updateMarketing($district_id);

        if ($attributes["type"] == "dealer") {
            $dealer = DealerV2::find($attributes["parent_id"]);

            if ($dealer) {
                $dealer->personel_id = $personel_id;
                $dealer->save();
            }
        } else if ($attributes["type"] == "sub_dealer") {
            $sub_dealer = SubDealer::find($attributes["parent_id"]);
            if ($sub_dealer) {
                $sub_dealer->personel_id = $personel_id;
                $sub_dealer->save();
            }
        }
    }

    public function resetSalesOrderDetailFee($product_id)
    {
        $sales_order_detail = SalesOrderDetail::query()
            ->whereYear("created_at", Carbon::now())
            ->where("product_id", $product_id)
            ->get();

        $sales_order_id = $sales_order_detail
            ->map(function ($detail, $key) {
                return $detail->sales_order_id;
            })
            ->values()
            ->toArray();

        /**
         * delete all log worker fee
         */
        $log_sales_fee = DB::table('log_worker_sales_fees')
            ->whereIn("sales_order_id", $sales_order_id)
            ->delete();

        /**
         * update fee marketing
         */
        $sales_order_detail = SalesOrderDetail::query()
            ->whereIn("sales_order_id", $sales_order_id)
            ->update([
                "marketing_fee" => 0,
                "marketing_fee_reguler" => 0,
            ]);

        /**
         * set fee sharing to unchecked
         * it will be calculated again
         */
        $fee_sharing = DB::table('fee_sharings')
            ->whereIn("sales_order_id", $sales_order_id)
            ->update([
                "is_checked" => 0,
            ]);
    }
}
