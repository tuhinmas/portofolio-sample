<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Entities\FeePositionHistory;

class FeePositionHistoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $fee_position_history = DB::table('fee_position_histories')
            ->whereNull("deleted_at")
            ->count();

        $fee_position = FeePosition::get();
        if ($fee_position_history <= 0) {

            $fee_positions = $fee_position
                ->map(function ($fee) {
                    return collect($fee)
                        ->forget(
                            "id",
                            "created_at",
                            "updated_at",
                            "deleted_at"
                        );
                })
                ->toArray();

            FeePositionHistory::create([
                "date_start" => now()->startOfYear(),
                "fee_position" => $fee_positions,
            ]);
        }
    }
}
