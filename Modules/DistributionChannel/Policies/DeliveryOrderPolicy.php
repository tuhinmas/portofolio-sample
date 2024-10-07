<?php

namespace Modules\DistributionChannel\Policies;

use App\Traits\ResponseHandler;
use App\Traits\RoleAccessModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\DistributionChannel\Entities\DeliveryOrder;

class DeliveryOrderPolicy
{
    use HandlesAuthorization, ResponseHandler, RoleAccessModel;

    /**
     * index policy
     *
     * @param User $user
     * @return void
     */
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, DeliveryOrder $deliveryOrder)
    {
        return true;
    }

    /**
     * Determine whether the user can create plants.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function update(User $user, DeliveryOrder $deliveryOrder)
    {
        return $this->deleteOrUpdateRules($deliveryOrder);
    }

    /**
     * Determine whether the user can delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, DeliveryOrder $deliveryOrder)
    {
        return $this->deleteOrUpdateRules($deliveryOrder);
    }

    /**
     * Determine whether the user can restore the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\SalesOrderV2  $sales_Order
     * @return mixed
     */
    public function restore(User $user, DeliveryOrder $deliveryOrder)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\SalesOrderV2  $sales_Order
     * @return mixed
     */
    public function forceDelete(User $user, DeliveryOrder $deliveryOrder)
    {
        return false;
    }

    /**
     * roles check
     *
     * @param [type] $model
     * @param [type] $model_2
     * @return void
     */
    public function responsePolicy($model, $model_2 = null)
    {
        if ($model->hasAnyRole($this->roleListToAccesssModel("DeliveryOrder"))) {
            return true;
        } else {
            $error = $this->response('05', 'unauthorized action', null);
            throw new HttpResponseException($error);
        }
    }

    /**
     * disptch can not delete in conditions
     * - has delivery order
     * - has reciving good even delivery is invalid
     *
     * @param DeliveryOrder $delivery_order
     * @return boolean
     */
    public function deleteOrUpdateRules(DeliveryOrder $delivery_order): bool
    {

        /**
         * in any case delivery order has valid receiving
         */
        if (self::hasReceivingGood($delivery_order)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Suarat jlan tidak bisa di batalkan karena sudah diterima",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        return true;
    }

    /**
     * is dispatch order has receiving good
     * even with invalid delivery order
     *
     * @param DeliveryOrder $delivery_order
     * @return boolean
     */
    public static function hasReceivingGood(DeliveryOrder $delivery_order): bool
    {
        $receiving_good = DB::table('receiving_goods as rg')
            ->whereNull("rg.deleted_at")
            ->where("rg.delivery_order_id", $delivery_order->id)
            ->where("rg.delivery_status", "2")
            ->first();

        return $receiving_good ? true : false;
    }
}
