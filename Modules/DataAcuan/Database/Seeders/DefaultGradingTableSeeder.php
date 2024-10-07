<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;

class DefaultGradingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $default_grading = Grading::where("name", "putih")->first();
        if ($default_grading) {
            $default_grading->default = "1";
            $default_grading->save();
        }
    }
}
