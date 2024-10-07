<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Entities\Category;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $categories = [
            ['name' => 'Supplier'],
            ['name' => 'Vendor'],
            ['name' => 'Dealer'],
            ['name' => 'Financial'],
            ['name' => 'Internal'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
