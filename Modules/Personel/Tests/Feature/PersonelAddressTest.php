<?php

namespace Modules\Personel\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Permission;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonelAddressTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_personel_address()
    {
        $response = $this->get(route('personel-address.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_personel_address()
    {
        $user = User::factory()->create();
        $response = $this->get(route('personel-address.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function personel_address_index()
    {
        $personel = personel::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('personel-address.index',
                [
                    'personel_id' => $personel->id,
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
        $personel = Personel::factory()->create();
        $country = Country::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('personel-address.store'), [
                "personel_id" => $personel->id,
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
    public function personel_address_edit()
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('personel-address.edit', [
                'personel_address' => $address->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function personel_address_update()
    {
        
        $personel = Personel::factory()->create();
        $country = Country::factory()->create();
        $address = Address::factory()->create();
        
        $response = $this->actingAs($this->user())
            ->put(route('personel-address.update', [
                'personel_address' => $address->id,
            ]), [
                "personel_id" => $personel->id,
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
    public function personel_address_delete()
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('personel-address.destroy', [
                'personel_address' => $address->id,
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
        $permission = Permission::firstOrCreate(['name' => 'crud personel']);
        $user->givePermissionTo(Permission::all());

        return $user;
    }
}
