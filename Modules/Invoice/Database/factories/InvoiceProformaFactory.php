<?php

namespace Modules\Invoice\Database\factories;

use Modules\Invoice\Entities\Invoice;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceProformaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Invoice\Entities\InvoiceProforma::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $proforma = Invoice::factory()->create();
        $personel = Personel::query()
            ->inRandomOrder(1)
            ->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->first();

        return [
            "issued_by" => $personel->id,
            "invoice_id" => $proforma->id,
            "link" => $this->faker->url,
            "confirmed_by" => $personel->id,
        ];
    }
}
