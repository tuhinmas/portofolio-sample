<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $products = [
            [
                'name' => 'pupuk kandang cair ampuh',
                'size' => '200 ml',
                'unit' => 'botol',
                'type' => 'liquid',
                'weight' => 0.3
            ],
            [
                'name' => 'Pupuk Super Cepat',
                'size' => '300 ml',
                'unit' => 'botol',
                'type' => 'liquid',
                'weight' => 0.4
            ],
        ];
        
        foreach($products as $product){
            Product::create($product);
        }
    }
}
