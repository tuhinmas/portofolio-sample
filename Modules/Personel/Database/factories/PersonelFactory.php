<?php
namespace Modules\Personel\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Country;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\Entity;
use Modules\DataAcuan\Entities\IdentityCard;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Religion;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Entities\Personel;

class PersonelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Personel\Entities\Personel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $religion = Religion::firstOrCreate([
            "name" => "Islam",
        ]);

        $country = Country::firstOrCreate([
            "code" => "ID",
        ], [
            "code3" => "IDN",
            "codeNumeric" => "360",
            "domain" => "id",
            "label_nl" => "Indonesië",
            "label_en" => "Indonesia, Republic of",
            "label_de" => "Indonesien",
            "label_es" => "Indonesia",
            "label_fr" => "Indonésie",
            "postCode" => "\\d{5}",
            "active" => 1,
        ]);

        $entity = Entity::firstOrCreate([
            "name" => "Pemerintah",
        ]);

        $organisation = Organisation::firstOrCreate([
            "entity_id" => $entity->id,
            "holding_id" => null,
            "prefix" => "CV",
            "name" => "Javamas Agrophos",
            "sufix" => null,
            "npwp" => "02.777.680.6-545.000",
            "tdp" => "120332000745",
            "ho" => "524/KPTS/X/2013",
            "siup" => "054/12-03/PK/II/2018",
            "note" => "javamas adalah",
            "chart" => null,
            "status" => 1,
            "telephone" => "02742910999",
            "hp" => "+6285290009000",
            "email" => "office@javamas.com",

        ]);

        $division = Division::factory()->create();
        $position = Position::firstOrCreate([
            "name" => "Regional Marketing (RM)",
        ], [
            "division_id" => $division->id,
            "job_description" => "",
            "job_definition" => "",
            "job_specification" => "",
            "is_mm" => false,
        ]);

        $identity_card_type = IdentityCard::firstOrCreate([
            "name" => "KTP"
        ]);

        return [
            'id' => $this->faker->uuid,
            "name" => "mastuhin",
            "position_id" => $position->id,
            "born_place" => "Gunung Kidul,",
            "born_date" => "1975-08-07",
            "religion_id" => $religion->id,
            "gender" => "L",
            "citizenship" => $country->id,
            "organisation_id" => $organisation->id,
            "identity_card_type" => $identity_card_type->id,
            "identity_number" => "12341234",
            "npwp" => "12341234",
            "blood_group" => "B negative",
            "join_date" => personel_join_days_get_fee() + 10,
        ];
    }

    public function marketingRM()
    {
        return $this->state(function (array $attributes) {
            $rm_position = Position::firstOrCreate([
                "name" => "Regional Marketing (RM)",
            ]);
            $rmc = Personel::factory()->marketingRMC()->create();

            return [
                'position_id' => $rm_position->id,
                "supervisor_id" => $rmc->id,
            ];
        });
    }

    public function marketingRMC()
    {
        return $this->state(function (array $attributes) {
            $rmc_position = Position::firstOrCreate([
                "name" => "Regional Marketing Coordinator (RMC)",
            ]);

            $mm = Personel::factory()->marketingMM()->create();
            return [
                'position_id' => $rmc_position->id,
                "supervisor_id" => $mm->id,
            ];
        });
    }

    public function marketingMDM()
    {
        return $this->state(function (array $attributes) {
            $mdm_position = Position::firstOrCreate([
                "name" => "Marketing District Manager (MDM)",
            ]);

            $mm = Personel::factory()->marketingMM()->create();
            return [
                'position_id' => $mdm_position->id,
                "supervisor_id" => $mm->id,
            ];
        });
    }

    public function marketingMM()
    {
        return $this->state(function (array $attributes) {
            $mm_position = Position::firstOrCreate([
                "name" => "Marketing Manager (MM)",
            ]);

            return [
                'position_id' => $mm_position->id,
            ];
        });
    }

    public function support()
    {
        return $this->state(function (array $attributes) {
            $support_position = Position::firstOrCreate([
                "name" => "support",
            ]);

            return [
                'position_id' => $support_position->id,
            ];
        });
    }
}
