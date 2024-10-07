<?php

namespace Modules\Personel\Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataAcuan\Entities\Country;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Religion;
use Modules\Organisation\Entities\Organisation;
use Tests\TestCase;

class PersonelTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function unauthenticated_user_cannot_create_personel()
    {
        $user = User::factory()->create();
        $religion = Religion::factory()->create();
        $position = Position::factory()->create();
        $country = Country::factory()->create();
        $organisation = Organisation::factory()->create();

        $response = $this->post(route('personel.store'), [
            "name" => "mastuhin",
            "supervisor" => "Mastuhin",
            "position_id" => $position->id,
            "born_place" => "Gunung Kidul,",
            "born_date" => "1975-08-07",
            "religion_id" => $religion->id,
            "gender" => "L",
            "citizenship" => $country->id,
            "organisation_id" => $organisation->id,
            "identity_card_type" => "1",
            "identity_number" => "12341234",
            "npwp" => "12341234",
            "blood_group" => "B",
            "rhesus" => "negative",
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_create_personel()
    {
        $user = User::factory()->create();
        $religion = Religion::factory()->create();
        $position = Position::factory()->create();
        $country = Country::factory()->create();
        $organisation = Organisation::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('personel.store'), [
                "name" => "mastuhin",
                "supervisor" => "Mastuhin",
                "position_id" => $position->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $religion->id,
                "gender" => "L",
                "citizenship" => $country->id,
                "organisation_id" => $organisation->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B",
                "rhesus" => "negative",
            ]);
        $response->assertStatus(403);
    }

    /**
     *
     *
     * @test
     */
    public function personel_store()
    {
        $religion = Religion::factory()->create();
        $position = Position::factory()->create();
        $country = Country::factory()->create();
        $organisation = Organisation::factory()->create();

        $response = $this->actingAs($this->user())
            ->post(route('personel.store'), [
                "name" => "mastuhin",
                "supervisor" => "Mastuhin",
                "position_id" => $position->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => $religion->id,
                "gender" => "L",
                "citizenship" => $country->id,
                "organisation_id" => $organisation->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B",
                "rhesus" => "negative",
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @return void
     */
    public function user()
    {
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'crud personel']);
        $user->givePermissionTo(Permission::all());

        return $user;
    }
}
