<?php

namespace Modules\DistributionChannel\Policies;

use App\Traits\ResponseHandler;
use App\Traits\RoleAccessModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\DistributionChannel\Entities\DispatchOrder;

class DispatchOrderPolicy
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

    public function view(User $user, DispatchOrder $dispatch_order)
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
    public function update(User $user, DispatchOrder $dispatch_order)
    {
        return $this->updateRules($dispatch_order);
    }

    /**
     * Determine whether the user can delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, DispatchOrder $dispatch_order)
    {
        return $this->deleteRules($dispatch_order);
    }

    /**
     * Determine whether the user can restore the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function restore(User $user, DispatchOrder $dispatch_order)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function forceDelete(User $user, DispatchOrder $dispatch_order)
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
        if ($model->hasAnyRole($this->roleListToAccesssModel("DispatchOrder"))) {
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
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public function updateRules(DispatchOrder $dispatch_order): bool
    {
        if (!$dispatch_order->is_active || $dispatch_order->status == "canceled") {
            if (self::hasDeliveryOrder($dispatch_order)) {
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Dispatch order tidak bisa dibatalkan karena sudah memiliki surat jalan aktif",
                    ],
                ], 422);

                throw new HttpResponseException($response);
            }

            /**
             * in any case dispatch order has valid receiving
             * but delivery is not valid, still ca not to
             * delete
             */
            if (self::hasReceivingGood($dispatch_order)) {
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Dispatch order tidak bisa dibatalkan karena sudah diterima",
                    ],
                ], 422);

                throw new HttpResponseException($response);
            }
        }

        return true;
    }

    public function deleteRules(DispatchOrder $dispatch_order): bool
    {
        if (self::hasDeliveryOrder($dispatch_order)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Dispatch order tidak bisa di batalkan karena sudah dikirim",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        /**
         * in any case dispatch order has valid receiving
         * but delivery is not valid, still ca not to
         * delete
         */
        if (self::hasReceivingGood($dispatch_order)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Dispatch order tidak bisa di batalkan karena sudah diterima",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        /**
         * if dispatch has already on active pickup order
         */
        if (self::hasPickupOrder($dispatch_order)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Dispatch order tidak bisa dibatalkan karena sudah dipickup",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }

        return true;
    }

    /**
     * is dispatch order has delivery order
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasDeliveryOrder(DispatchOrder $dispatch_order): bool
    {
        $last_delivery_order = DB::table('delivery_orders')
            ->where("dispatch_order_id", $dispatch_order->id)
            ->where("status", "send")
            ->whereNull("deleted_at")
            ->orderBy("date_delivery", "desc")
            ->orderBy("updated_at", "desc")
            ->first();

        return $last_delivery_order ? true : false;
    }

    /**
     * is dispatch order has receiving good
     * even with invalid delivery order
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasReceivingGood(DispatchOrder $dispatch_order): bool
    {
        $receiving_good = DB::table('delivery_orders as dor')
            ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
            ->whereNull("dor.deleted_at")
            ->whereNull("rg.deleted_at")
            ->where("dispatch_order_id", $dispatch_order->id)
            ->where("rg.delivery_status", "2")
            ->orderBy("dor.date_delivery", "desc")
            ->orderBy("dor.updated_at", "desc")
            ->first();

        return $receiving_good ? true : false;
    }

    /**
     * is dispatch order has valid pickup
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasPickupOrder(DispatchOrder $dispatch_order): bool
    {
        $has_pickup_order = DB::table('pickup_orders as po')
            ->join("pickup_order_dispatches as pod", "po.id", "pod.pickup_order_id")
            ->whereIn("po.status", ["loaded", "planned", "revised"])
            ->where("pod.dispatch_id", $dispatch_order->id)
            ->whereNull("po.deleted_at")
            ->whereNull("pod.deleted_at")
            ->select("po.*")
            ->first();

        return $has_pickup_order ? true : false;
    }
}
