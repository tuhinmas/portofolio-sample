<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Modules\SalesOrder\Entities\SalesOrder;

class SalesOrderTestExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        /* get dealer id list */
        $dealers = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", "f325080d-e0ac-48bb-bf61-0ef8e2757eed")->pluck("id")->toArray();

        /* get sub dealer id list */
        $sub_dealers = DB::table('sub_dealers')->whereNull("deleted_at")->where("personel_id", "f325080d-e0ac-48bb-bf61-0ef8e2757eed")->pluck("id")->toArray();
        $stores = array_unique(array_merge($dealers, $sub_dealers));

        return SalesOrder::query()
            ->whereIn("store_id", $stores)
            ->where(function ($QQQ) use ($stores) {
                return $QQQ
                    ->whereHas("invoice", function ($QQQ) use ($stores) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("delivery_status", "2")
                                    ->orWhere("payment_status", "!=", "settle");
                            });
                    })
                    ->orWhere(function ($QQQ) use ($stores) {
                        return $QQQ
                            ->whereDoesntHave("invoiceHasOne")
                            ->whereNull("link");
                    });
            })
            ->get();
    }
}
