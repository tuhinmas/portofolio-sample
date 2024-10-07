<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\BussinessSectorCategory;

class BussinessSectorCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $bussiness_sector_categories = [
            [
                'name' => 'Retail',
            ],
            [
                'name' => 'Service',
            ],
        ];
        foreach ($bussiness_sector_categories as $bussiness_sector_category) {
            BussinessSectorCategory::create($bussiness_sector_category);
        }
    }
}
