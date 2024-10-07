<?php

namespace Modules\SalesOrder\Actions\Order\Origin;

use Illuminate\Support\Facades\DB;
use Modules\Distributor\Actions\GetDistributorActiveContractAction;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Invoice\Entities\CreditMemoDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

class GenerateSalesOrderOriginFromMemoAction
{
    protected static $order_detail;

    public function __construct(
        protected SalesOrderOrigin $sales_order_origin,
    ) {
        self::$order_detail = collect();
    }

    public function __invoke(CreditMemo $credit_memo, bool $is_cancel_memo = false)
    {
        $credit_memo->load([
            "creditMemoDetail",
            "origin.salesOrderOnly",
        ]);
        $distributor_active_contract = (new GetDistributorActiveContractAction)($credit_memo->dealer_id);

        if ($distributor_active_contract) {

            if ($is_cancel_memo) {
                self::$order_detail = DB::table('sales_order_details')
                    ->where("sales_order_id", $credit_memo->origin?->sales_order_id)
                    ->whereNull("deleted_at")
                    ->get();
            }

            $credit_memo
                ->creditMemoDetail
                ->each(function ($credit_memo_detail) use ($is_cancel_memo, $distributor_active_contract, $credit_memo) {
                    switch ($is_cancel_memo) {

                        /**
                         * get order origin according distributor and product
                         * to reduce stock, memo mean stock need to reduce
                         */
                        case false:
                            $quantity_return = $credit_memo_detail->quantity_return;
                            $stock_to_reduce = $this->sales_order_origin->query()
                                ->whereDate("confirmed_at", ">=", $distributor_active_contract->contract_start)
                                ->whereDate("confirmed_at", "<=", $distributor_active_contract->contract_end)
                                ->where("store_id", $distributor_active_contract->dealer_id)
                                ->whereRaw("stock_ready > 0")
                                ->where("product_id", $credit_memo_detail->product_id)
                                ->limit(15)
                                ->orderBy("confirmed_at")
                                ->get()
                                ->each(function ($origin) use (&$quantity_return) {
                                    if ($origin->stock_ready >= $quantity_return) {
                                        $origin->stock_ready -= $quantity_return;
                                        $origin->stock_out += $quantity_return;
                                        $origin->note = ($origin->note ? $origin->note . ", " : "") . "reduction from credit memo";
                                        $origin->save();
                                        $quantity_return = 0;
                                        return false;
                                    }

                                    /**
                                     * mean stock < qty return
                                     * fill stock out as full
                                     */
                                    $quantity_return -= $origin->stock_ready;
                                    $origin->stock_ready = 0;
                                    $origin->stock_out = $origin->quantity_from_origin;
                                    $origin->note = ($origin->note ? $origin->note . ", " : "") . "reduction from credit memo";
                                    $origin->save();

                                    if ($quantity_return <= 0) {
                                        return false;
                                    }
                                });

                            return $stock_to_reduce;
                            break;

                        /**
                         * if credit memo canceled, than stock need to returned
                         * date of stock is same as origin of memo
                         */
                        default:
                            $order_detail = self::$order_detail->filter(fn($order_detail) => $order_detail->product_id == $credit_memo_detail->product_id)->first();
                            $origin = $this->sales_order_origin->create([
                                "sales_order_detail_id" => $order_detail->id,
                                "sales_order_id" => $credit_memo->origin->sales_order_id,
                                "direct_id" => $credit_memo->origin->sales_order_id,
                                "sales_order_detail_direct_id" => $order_detail->id,
                                "parent_id" => $credit_memo->origin->sales_order_id,
                                "sales_order_detail_parent_id" => $order_detail->id,
                                "product_id" => $order_detail->product_id,
                                "direct_price" => $order_detail->unit_price,
                                "quantity_from_origin" => $credit_memo_detail->quantity_return,
                                "current_stock" => $credit_memo_detail->quantity_return,
                                "quantity_order" => $order_detail->quantity,
                                "type" => "1",
                                "store_id" => $credit_memo->dealer_id,
                                "lack_of_stock" => 0,
                                "stock_ready" => $credit_memo_detail->quantity_return,
                                "is_splited_origin" => 0,
                                "stock_out" => 0,
                                "confirmed_at" => $credit_memo->origin->created_at,
                                "level" => 1,
                                "note" => "new stock from credit memo canceled",
                            ]);
                            break;
                    }
                });

        }

        return $credit_memo;
    }
}
