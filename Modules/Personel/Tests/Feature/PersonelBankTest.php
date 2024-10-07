<?php

namespace Modules\Personel\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use Modules\DataAcuan\Entities\Bank;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelBank;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonelBankTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_personel_bank()
    {
        $user = User::factory()->create();
        $response = $this->get(route('personel-bank.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_personel_bank()
    {
        $user = User::factory()->create();
        $response = $this->get(route('personel-bank.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function personel_bank_index()
    {
        $personel = Personel::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('personel-bank.index',
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
    public function oragnisation_bank_store()
    {
        $personel = Personel::factory()->create();
        $country = Country::factory()->create();
        $bank = Bank::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('personel-bank.store'), [
                'pemilik' => "mastuhin",
                'personel_id' => $personel->id,
                'bank_id' => $bank->id,
                'cabang' => 'sleman',
                'rekening' => '12345123',
                'swift_code' => '223412',
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function personel_bank_edit()
    {
        $bank = PersonelBank::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('personel-bank.edit', [
                'personel_bank' => $bank->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function personel_bank_update()
    {

        $perosnel_bank = PersonelBank::factory()->create();
        $personel = Personel::factory()->create();
        $bank = Bank::factory()->create();

        $response = $this->actingAs($this->user())
            ->put(route('personel-bank.update', [
                'personel_bank' => $perosnel_bank->id,
                'pemilik' => "mastuhin",
                'personel_id' => $personel->id,
                'bank_id' => $bank->id,
                'cabang' => 'sleman',
                'rekening' => '12345123',
                'swift_code' => '223412',
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function personel_bank_delete()
    {
        $perosnel_bank = PersonelBank::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('personel-bank.destroy', [
                'personel_bank' => $perosnel_bank->id,
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
