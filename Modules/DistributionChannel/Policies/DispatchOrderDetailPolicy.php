<?php

namespace Modules\DistributionChannel\Policies;

use App\Traits\ResponseHandler;
use App\Traits\RoleAccessModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

class DispatchOrderDetailPolicy
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

    public function view(User $user, DispatchOrderDetail $dispatch_order_detail)
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
    public function update(User $user, DispatchOrderDetail $dispatch_order_detail)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @return mixed
     */
    public function delete(User $user, DispatchOrderDetail $dispatch_order_detail)
    {
        return $this->deleteRules($dispatch_order_detail);
    }

    /**
     * Determine whether the user can restore the SalesOrderV2.
     *
     * @param  Modules\Authentication\Entities\User  $user
     * @param  \App\Models\SalesOrderV2  $sales_Order
     * @return mixed
     */
    public function restore(User $user, DispatchOrderDetail $dispatch_order_detail)
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
    public function forceDelete(User $user, DispatchOrderDetail $dispatch_order_detail)
    {
        return false;
    }

    /**
     * disptch can not delete in conditions
     * - has delivery order
     * - has reciving good even delivery is invalid
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public function updateRules(DispatchOrderDetail $dispatch_order_detail): bool
    {
        switch (true) {

            /**
             * in any case product was received even
             * with invalid delivery order or
             * dispatch order
             */
            case self::hasPickupOrder($dispatch_order_detail):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Dispatch order detail tidak bisa diupdate karena sudah dipickup",
                    ],
                ], 422);

                throw new HttpResponseException($response);
                break;

            /**
             * if dispoatch has valid pickup, then can not to be deleted
             */
            case self::hasReceivingGoodDetail($dispatch_order_detail):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Dispatch order detail tidak bisa diupdate karena sudah diterima",
                    ],
                ], 422);

                throw new HttpResponseException($response);
                break;

            default:
                break;
        }

        return true;
    }

    /**
     * disptch can not delete in conditions
     * - has delivery order
     * - has reciving good even delivery is invalid
     *
     * @param DispatchOrderDetail $dispatch_order_detail
     * @return boolean
     */
    public function deleteRules(DispatchOrderDetail $dispatch_order_detail): bool
    {
        switch (true) {

            /**
                 * in any case product was received even
                 * with invalid delivery order or
                 * dispatch order
                 */
            case self::hasPickupOrder($dispatch_order_detail):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Dispatch order detail tidak bisa di hapus karena sudah dipickup",
                    ],
                ], 422);

                throw new HttpResponseException($response);
                break;

            /**
                 * if dispoatch has valid pickup, then can not to be deleted
                 */
            case self::hasReceivingGoodDetail($dispatch_order_detail):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Dispatch order detail tidak bisa di hapus karena sudah diterima",
                    ],
                ], 422);

                throw new HttpResponseException($response);
                break;

            default:
                break;
        }

        return true;
    }

    /**
     * is dispatch order has receiving good even with invalid delivery order
     * delivered, broekn or incorrect procut
     *
     * @param DispatchOrderDetail $dispatch_order_detail
     * @return boolean
     */
    public static function hasReceivingGoodDetail(DispatchOrderDetail $dispatch_order_detail): bool
    {
        $receiving_good_detail = DB::table('receiving_good_details as rgd')
            ->join("receiving_goods as rg", "rg.id", "rgd.receiving_good_id")
            ->join("delivery_orders as dor", "dor.id", "rg.delivery_order_id")
            ->whereNull("dor.deleted_at")
            ->whereNull("rgd.deleted_at")
            ->whereNull("rg.deleted_at")
            ->where("dor.dispatch_order_id", $dispatch_order_detail->id_dispatch_order)
            ->where("rgd.product_id", $dispatch_order_detail->id_product)
            ->where("rg.delivery_status", "2")
            ->first();

        return $receiving_good_detail ? true : false;
    }

    /**
     * is dispatch order has valid pickup
     *
     * @param DispatchOrderDetail $dispatch_order_detail
     * @return boolean
     */
    public static function hasPickupOrder(DispatchOrderDetail $dispatch_order_detail): bool
    {
        $has_pickup_order = DB::table('pickup_orders as po')
            ->join("pickup_order_dispatches as pod", "po.id", "pod.pickup_order_id")
            ->whereIn("po.status", ["loaded", "planned", "revised"])
            ->where("pod.dispatch_id", $dispatch_order_detail->id_dispatch_order)
            ->whereNull("po.deleted_at")
            ->whereNull("pod.deleted_at")
            ->select("po.*")
            ->first();

        return $has_pickup_order ? true : false;
    }
}
