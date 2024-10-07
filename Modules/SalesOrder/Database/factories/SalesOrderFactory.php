<?php
namespace Modules\SalesOrder\Database\factories;

use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\StatusFee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Distributor\Entities\DistributorContract;

class SalesOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\SalesOrder\Entities\SalesOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        $dealer = Dealer::factory()->create([
            "personel_id" => $personel->id
        ]);
        $distributor = Dealer::factory()->create();
        $contract = DistributorContract::factory()->create([
            "dealer_id" => $distributor->id
        ]);

        return [
            "personel_id" => $dealer->personel_id,
            "store_id" => $dealer->id,
            "distributor_id" => $distributor?->id,
            "type" => "2",
            "model" => 1,
            "grading_id" => "1",
            "delivery_address" => "Jawa",
            "recipient_phone_number" => "8123456",
            "estimated_done" => "2023-01-03",
            "status_fee_id" => StatusFee::inRandomOrder(1)->first()->id,
            "note" => "factory",
        ];
    }
}
