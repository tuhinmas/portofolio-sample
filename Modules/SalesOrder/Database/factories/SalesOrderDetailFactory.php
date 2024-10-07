<?php
namespace Modules\SalesOrder\Database\factories;

use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\SalesOrder\Entities\SalesOrderDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $product = Product::factory()->create();
        $package = Package::factory()->create([
            'product_id' => $product->id,
            'quantity_per_package' => 12,
            'weight' => 10,
        ]);
        $price = Price::factory()->create([
            "product_id" => $product->id,
        ]);
        $sales_order = SalesOrder::factory()->create();
        return [
            "sales_order_id" => $sales_order->id,
            "product_id" => $product->id,
            "quantity" => 100,
            "quantity_order" => 100,
            "unit_price" => $price->price,
            "total" => 100 * $price->price,
            "package_id" => $package->id,
            "package_name" => $package->packaging,
            "quantity_on_package" => $package->quantity_per_package,
            "package_weight" => $package->weight,
            "agency_level_id" => $price->agency_level_id,
        ];
    }
}
