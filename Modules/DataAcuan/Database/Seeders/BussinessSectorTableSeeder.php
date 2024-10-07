<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\BussinessSector;
use Modules\DataAcuan\Entities\BussinessSectorCategory;

class BussinessSectorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $category = BussinessSectorCategory::inRandomOrder(1)->first();
        $sector = [
            'name' => 'Agrobisnis',
            'category_id' => $category->id,
        ];
        BussinessSector::create($sector);
    }
}
