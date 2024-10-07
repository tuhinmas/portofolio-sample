<?php

namespace Modules\SalesOrder\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use ogrrd\CsvIterator\CsvIterator;

class DirectSaleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/SalesOrder/Database/Seeders/csv/SalesOrderSeeder.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        $order_differentiator = null;
        $sales_order = null;

        foreach ($rows as $row) {
            $row = (object) $row;
            if ($row->type == "1") {
                $dealers = DB::table('dealers')->where("dealer_id", $row->dealer_id)->whereNull("deleted_at")->get();
                if ($dealers) {
                    foreach ($dealers as $dealer) {
                        /* create new order or not */
                        $counter_id = null;
                        $distributor_id = null;

                        if ($row->counter_id) {
                            $counter_id = $row->counter_id;
                        }
                        if ($row->distributor_id) {
                            $distributor_id = $row->distributor_id;
                        }
                        if ($order_differentiator != $row->order_differentiator) {
                            $sales_order = SalesOrder::create([
                                'store_id' => $dealer->id,
                                'personel_id' => $row->personel_id,
                                'counter_id' => $counter_id,
                                'recipient_phone_number' => $row->recipient_phone_number,
                                'model' => $row->model,
                                'type' => $row->type,
                                'distributor_id' => $distributor_id,
                                'reference_number' => $row->reference_number,
                                'link' => $row->link,
                                'counter_fee' => $row->counter_fee,
                                'payment_method_id' => $row->payment_method_id,
                                'delivery_location' => $row->delivery_location,
                                'total' => $row->total,
                                'status' => $row->status,
                                'note' => $row->note,
                                'proforma' => $row->proforma,
                            ]);
                        }

                        $agency_level = DB::table('agency_levels')->where("name", $row->agency_level)->first();

                        $sales_order_detail = SalesOrderDetail::create([
                            'sales_order_id' => $sales_order->id,
                            'product_id' => $row->product_id,
                            'quantity' => $row->quantity,
                            'unit_price' => $row->unit_price,
                            'total' => $row->detail_total,
                            'agency_level_id' => $agency_level->id,
                            'retail_point' => $row->retail_point,
                            'marketing_point' => $row->marketing_point,
                            'marketing_fee' => $row->marketing_fee,
                            'package_id' => $row->package_id,
                            'quantity_on_package' => $row->quantity_on_package,
                        ]);
                    }
                }
                /* indirect sale */
            } else {
                $dealers = null;
                if ($row->model == "1") {
                    $dealers = DB::table('dealers')->where("dealer_id", $row->dealer_id)->whereNull("deleted_at")->get();
                }
                else {
                    $dealers = DB::table('sub_dealers')->where("sub_dealer_id", $row->dealer_id)->whereNull("deleted_at")->get();
                }

                if ($dealers) {
                    foreach ($dealers as $dealer) {
                        /* create new order or not */
                        $counter_id = null;
                        $distributor_id = null;

                        if ($row->counter_id) {
                            $counter_id = $row->counter_id;
                        }
                        if ($row->distributor_id) {
                            $distributor_id = $row->distributor_id;
                            $distributor_id = DB::table('dealers')->where("dealer_id", $distributor_id)->whereNull("deleted_at")->first();
                        }
                        if ($order_differentiator != $row->order_differentiator) {
                            $sales_order = SalesOrder::create([
                                'store_id' => $dealer->id,
                                'personel_id' => $row->personel_id,
                                'counter_id' => $counter_id,
                                'recipient_phone_number' => $row->recipient_phone_number,
                                'model' => $row->model,
                                'type' => $row->type,
                                'distributor_id' => $distributor_id->id,
                                'reference_number' => $row->reference_number,
                                'link' => $row->link,
                                'counter_fee' => $row->counter_fee,
                                'payment_method_id' => $row->payment_method_id,
                                'delivery_location' => $row->delivery_location,
                                'total' => $row->total,
                                'status' => $row->status,
                                'note' => $row->note,
                                'proforma' => $row->proforma,
                            ]);
                        }

                        $agency_level = DB::table('agency_levels')->where("name", $row->agency_level)->first();

                        $sales_order_detail = SalesOrderDetail::create([
                            'sales_order_id' => $sales_order->id,
                            'product_id' => $row->product_id,
                            'quantity' => $row->quantity,
                            'unit_price' => $row->unit_price,
                            'total' => $row->detail_total,
                            'agency_level_id' => $agency_level->id,
                            'retail_point' => $row->retail_point,
                            'marketing_point' => $row->marketing_point,
                            'marketing_fee' => $row->marketing_fee,
                            'package_id' => $row->package_id,
                            'quantity_on_package' => $row->quantity_on_package,
                        ]);
                    }
                }
            }

            $order_differentiator = $row->order_differentiator;
        }
    }
}
