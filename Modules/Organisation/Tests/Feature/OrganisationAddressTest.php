<?php

namespace Modules\Organisation\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Permission;
use Modules\DataAcuan\Entities\Country;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganisationAddressTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_organisation_address()
    {
        $user = User::factory()->create();
        $response = $this->get(route('organisation-address.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_organisation_address()
    {
        $user = User::factory()->create();
        $response = $this->get(route('organisation-address.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function organisation_address_index()
    {
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('organisation-address.index',
                [
                    'organisation_id' => $organisation->id,
                ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function oragnisation_address_store()
    {
        $organisation = Organisation::factory()->create();
        $country = Country::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('organisation-address.store'), [
                "organisation_id" => $organisation->id,
                "address_type" => "Gudang",
                "jalan" => "Jl. Jakal",
                "kecamatan" => "Ngemplak",
                "kabupaten" => "Sleman",
                "provinsi" => "Daerah Istimewa Yogyakarta",
                "kode_pos" => "42825",
                "link_gmaps" => "https://www.google.com/maps/place/Ngatiran+Mebel/@-7.7283852,110.4161379,19z/data=!4m5!3m4!1s0x2e7a59d4dfd731ef:0x782a722b0016a1b!8m2!3d-7.7284198!4d110.4161217",
                "country_id" => $country->id,
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function organisation_address_edit()
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('organisation-address.edit', [
                'organisation_address' => $address->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function organisation_address_update()
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user())
            ->put(route('organisation-address.update', [
                'organisation_address' => $address->id,
            ]), [
                'address_type' => 'telephone',
                'coontact_detail' => '12312131213',
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function organisation_address_delete()
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('organisation-address.destroy', [
                'organisation_address' => $address->id,
            ]));
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
        $permission = Permission::firstOrCreate(['name' => 'crud holding-organisation']);
        $user->givePermissionTo(Permission::all());

        return $user;
    }
}
