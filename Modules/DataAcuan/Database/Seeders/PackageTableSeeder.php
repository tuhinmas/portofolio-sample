<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;

class PackageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $product = Product::inRandomOrder()->first();
        $packages = [
            [
                'product_id' => $product->id,
                'packaging' => 'dus',
                'quantity_per_package' => '12',
                'unit' => 'botol',            
                'weight' => '10',
            ],
            [
                'product_id' => $product->id,
                'packaging' => 'plastik',
                'quantity_per_package' => '6',
                'unit' => 'botol',            
                'weight' => '5',
                'isActive' => '1'
            ],
        ];
        foreach($packages as $package){
            Package::create($package);
        }
    }
}
