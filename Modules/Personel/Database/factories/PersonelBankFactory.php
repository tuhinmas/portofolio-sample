<?php
namespace Modules\Personel\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Bank;
use Modules\Personel\Entities\Personel;

class PersonelBankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Personel\Entities\PersonelBank::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        $bank = Bank::factory()->create();

        return [
            'owner' => $personel->name,
            'personel_id' => $personel->id,
            'bank_id' => $bank->id,
            'branch' => 'sleman',
            'rek_number' => $this->faker->randomNumber,
            'swift_code' => '223412',
        ];
    }
}
