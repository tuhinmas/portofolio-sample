<?php

namespace Modules\Organisation\Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Modules\Organisation\Entities\Holding;
use Modules\Organisation\Entities\Organisation;
use Tests\TestCase;

class HoldingOrganisationTest extends TestCase
{
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_Holding()
    {
        $user = User::factory()->create();
        $response = $this->get(route('holding.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_Holding()
    {
        $user = User::factory()->create();
        $response = $this->get(route('holding.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function holding_organisation_index()
    {
        $holding = Holding::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('holding-organisation.index'), [
                'holding_id' => $holding->id,
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_organisation_store()
    {
        $holding = Holding::factory()->create();
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('holding-organisation.store'), [
                'holding_id' => $holding->id,
                'organisation_id' => $organisation->id,
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_organisation_edit()
    {
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('holding-organisation.edit', [
                'holding_organisation' => $organisation->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_organisation_update()
    {
        $holding = Holding::factory()->create();
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->put(route('holding-organisation.update', [
                'holding_organisation' => $organisation->id,
            ]), [
                'holding_id' => $holding->id,
                'organisation_id' => $organisation->id,
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_organisation_delete()
    {
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('holding-organisation.destroy', [
                'holding_organisation' => $organisation->id])
            );
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
