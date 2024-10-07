<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class FeePositionHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\FeePositionHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $fee_positions = DB::table('fee_positions as fp')
            ->leftJoin("positions as po", "po.id", "fp.position_id")
            ->whereNull("fp.deleted_at")
            ->whereNull("po.deleted_at")
            ->select("fp.*", "po.name as position_name")
            ->get();

        $agency_level_R3 = DB::table('agency_levels')
            ->whereIn("name", agency_level_R3())
            ->first();

        // $fee_position = collect([

        //     /* MM */
        //     [
        //         "position_id" => $fee_positions->filter(fn($fee) => $fee->is_mm == true)->first()->position_id,
        //         "fee" => 1.50,
        //         "follow_up" => 0,
        //         "fee_cash" => 1.00,
        //         "fee_cash_minimum_order" => $agency_level_R3->id,
        //         "fee_sc_on_order" => 50,
        //         "maximum_settle_days" => 63,
        //         "settle_from" => 1,
        //         "fee_as_marketing" => false,
        //         "is_applicator" => false,
        //         "is_mm" => true,
        //     ],

        //     /* assistant MDM */
        //     [
        //         "position_id" => $fee_positions->filter(fn($fee) => in_array($fee->position_name, position_assistant_MDM()))->first()->position_id,
        //         "fee" => 2.00,
        //         "follow_up" => 1,
        //         "fee_cash" => 1.00,
        //         "fee_cash_minimum_order" => $agency_level_R3->id,
        //         "fee_sc_on_order" => 50,
        //         "maximum_settle_days" => 63,
        //         "settle_from" => 1,
        //         "fee_as_marketing" => false,
        //         "is_applicator" => false,
        //         "is_mm" => false,
        //     ],

        //     /* MDM */
        //     [
        //         "position_id" => $fee_positions->filter(fn($fee) => in_array($fee->position_name, position_mdm()))->first()->position_id,
        //         "fee" => 3.50,
        //         "follow_up" => 1,
        //         "fee_cash" => 1.00,
        //         "fee_cash_minimum_order" => $agency_level_R3->id,
        //         "fee_sc_on_order" => 50,
        //         "maximum_settle_days" => 63,
        //         "settle_from" => 1,
        //         "fee_as_marketing" => false,
        //         "is_applicator" => false,
        //         "is_mm" => false,
        //     ],

        //     /* applicator */
        //     [
        //         "position_id" => $fee_positions->filter(fn($fee) => $fee->is_applicator == true)->first()->position_id,
        //         "fee" => 5.00,
        //         "follow_up" => 0,
        //         "fee_cash" => 1.00,
        //         "fee_cash_minimum_order" => $agency_level_R3->id,
        //         "fee_sc_on_order" => 50,
        //         "maximum_settle_days" => 63,
        //         "settle_from" => 1,
        //         "fee_as_marketing" => false,
        //         "is_applicator" => true,
        //         "is_mm" => false,
        //     ],

        //     /* RMC */
        //     [
        //         "position_id" => $fee_positions->filter(fn($fee) => in_array($fee->position_name, position_rmc()))->first()->position_id,
        //         "fee" => 15.00,
        //         "follow_up" => 1,
        //         "fee_cash" => 1.00,
        //         "fee_cash_minimum_order" => $agency_level_R3->id,
        //         "fee_sc_on_order" => 50,
        //         "maximum_settle_days" => 63,
        //         "settle_from" => 1,
        //         "fee_as_marketing" => false,
        //         "is_applicator" => false,
        //         "is_mm" => false,
        //     ],

        //     /* RM */
        //     [
        //         "position_id" => $fee_positions->filter(fn($fee) => in_array($fee->position_name, position_rm()))->first()->position_id,
        //         "fee" => 75.00,
        //         "follow_up" => 1,
        //         "fee_cash" => 1.00,
        //         "fee_cash_minimum_order" => $agency_level_R3->id,
        //         "fee_sc_on_order" => 50,
        //         "maximum_settle_days" => 63,
        //         "settle_from" => 1,
        //         "fee_as_marketing" => true,
        //         "is_applicator" => false,
        //         "is_mm" => false,
        //     ],
        // ])
        //     ->reject(function ($fee) use ($fee_positions) {
        //         return !in_array($fee["position_id"], $fee_positions->toArray());
        //     })
        //     ->toArray();

        $payload = $fee_positions->map(function ($fee) {
            $history = [
                "position_id" => $fee->position_id,
                "fee" => $fee->fee,
                "follow_up" => $fee->follow_up,
                "fee_cash" => $fee->fee_cash,
                "fee_cash_minimum_order" => $fee->fee_cash_minimum_order,
                "fee_sc_on_order" => $fee->fee_sc_on_order,
                "maximum_settle_days" => $fee->maximum_settle_days,
                "settle_from" => $fee->settle_from,
                "fee_as_marketing" => $fee->fee_as_marketing,
                "is_applicator" => $fee->is_applicator,
                "is_mm" => $fee->is_mm,
            ];

            return $history;
        })
            ->toArray();

        return [
            "date_start" => now(),
            "is_checked" => false,
            "fee_position" => $payload,
        ];
    }
}
