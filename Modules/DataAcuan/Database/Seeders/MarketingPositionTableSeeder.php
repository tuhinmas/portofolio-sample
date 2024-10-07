<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\Position;

class MarketingPositionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        
        $marketings = [
            [
                'name' => 'Marketing Manager (MM)',
                'division_id' => Division::where('name', 'Sales & Marketing')->first()->id,
                'job_description' => 'handle marketing sk ndunyo',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan',
            ],
            [
                'name' => 'Marketing District Manager (MDM)',
                'division_id' => Division::where('name', 'Sales & Marketing')->first()->id,
                'job_description' => 'handle marketing sk propinsi',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan',
            ],
            [
                'name' => 'Assistant MDM',
                'division_id' => Division::where('name', 'Sales & Marketing')->first()->id,
                'job_description' => 'handle marketing sk propinsi',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan',
            ],
            [
                'name' => 'Regional Marketing Coordinator (RMC)',
                'division_id' => Division::where('name', 'Sales & Marketing')->first()->id,
                'job_description' => 'handle marketing sk sub region',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan',
            ],
            [
                'name' => 'Regional Marketing (RM)',
                'division_id' => Division::where('name', 'Sales & Marketing')->first()->id,
                'job_description' => 'handle marketing sk kabupaten',
                'job_definition' => 'dodolan',
                'job_specification' => 'dodolan',
            ],
        ];

        foreach ($marketings as $marketing) {
            Position::create($marketing);
        }
    }
}
