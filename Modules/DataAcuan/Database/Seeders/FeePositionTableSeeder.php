<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;

class FeePositionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $positions = DB::table('positions')->whereNull("deleted_at")->get();
        $agency_level_R3 = DB::table('agency_levels')
            ->whereIn("name", agency_level_R3())
            ->first();

        $fee_position = collect([

            /* MM */
            [
                "position_id" => $positions->filter(fn($fee) => $fee->is_mm == true)->first()->id,
                "fee" => 1.50,
                "follow_up" => 0,
                "fee_cash" => 1.00,
                "fee_cash_minimum_order" => $agency_level_R3->id,
                "fee_sc_on_order" => 50,
                "maximum_settle_days" => 63,
                "settle_from" => 1,
                "fee_as_marketing" => false,
                "is_applicator" => false,
                "is_mm" => true,
            ],

            /* assistant MDM */
            [
                "position_id" => $positions->filter(fn($fee) => in_array($fee->name, position_assistant_MDM()))->first()->id,
                "fee" => 2.00,
                "follow_up" => 1,
                "fee_cash" => 1.00,
                "fee_cash_minimum_order" => $agency_level_R3->id,
                "fee_sc_on_order" => 50,
                "maximum_settle_days" => 63,
                "settle_from" => 1,
                "fee_as_marketing" => false,
                "is_applicator" => false,
                "is_mm" => false,
            ],

            /* MDM */
            [
                "position_id" => $positions->filter(fn($fee) => in_array($fee->name, position_mdm()))->first()->id,
                "fee" => 3.50,
                "follow_up" => 1,
                "fee_cash" => 1.00,
                "fee_cash_minimum_order" => $agency_level_R3->id,
                "fee_sc_on_order" => 50,
                "maximum_settle_days" => 63,
                "settle_from" => 1,
                "fee_as_marketing" => false,
                "is_applicator" => false,
                "is_mm" => false,
            ],

            /* applicator */
            [
                "position_id" => $positions->filter(fn($fee) => in_array($fee->name, position_applicator()))->first()->id,
                "fee" => 5.00,
                "follow_up" => 0,
                "fee_cash" => 1.00,
                "fee_cash_minimum_order" => $agency_level_R3->id,
                "fee_sc_on_order" => 50,
                "maximum_settle_days" => 63,
                "settle_from" => 1,
                "fee_as_marketing" => false,
                "is_applicator" => true,
                "is_mm" => false,
            ],

            /* RMC */
            [
                "position_id" => $positions->filter(fn($fee) => in_array($fee->name, position_rmc()))->first()->id,
                "fee" => 15.00,
                "follow_up" => 1,
                "fee_cash" => 1.00,
                "fee_cash_minimum_order" => $agency_level_R3->id,
                "fee_sc_on_order" => 50,
                "maximum_settle_days" => 63,
                "settle_from" => 1,
                "fee_as_marketing" => false,
                "is_applicator" => false,
                "is_mm" => false,
            ],

            /* RM */
            [
                "position_id" => $positions->filter(fn($fee) => in_array($fee->name, position_rm()))->first()->id,
                "fee" => 75.00,
                "follow_up" => 1,
                "fee_cash" => 1.00,
                "fee_cash_minimum_order" => $agency_level_R3->id,
                "fee_sc_on_order" => 50,
                "maximum_settle_days" => 63,
                "settle_from" => 1,
                "fee_as_marketing" => true,
                "is_applicator" => false,
                "is_mm" => false,
            ],
        ])
            ->reject(function ($fee) use ($positions) {
                return !in_array($fee["position_id"], $positions->pluck("id")->toArray());
            })
            ->toArray();
        
        foreach ($fee_position as $fee) {
            FeePosition::create($fee);
        }
    }
}
