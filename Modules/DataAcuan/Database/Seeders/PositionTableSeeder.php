<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\Position;

class PositionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $marketing_division = Division::where("name", "Sales & Marketing")->firstOrFail();
        foreach (marketing_positions() as $position) {
            Position::create([
                'name' => $position,
                'division_id' => $marketing_division->id,
                'job_description' => 'lorem ipsum dolor sit amet',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan keliliing',
                "is_mm" => ($position == "Marketing Manager (MM)" ? true: false)
            ]);
        }

        foreach (support_positions() as $position) {
            Position::create([
                'name' => $position,
                'division_id' => $marketing_division->id,
                'job_description' => 'lorem ipsum dolor sit amet',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan keliliing',
            ]);
        }
    }
}
