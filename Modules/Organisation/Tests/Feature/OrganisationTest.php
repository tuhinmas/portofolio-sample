<?php

namespace Modules\Organisation\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use Modules\Organisation\Entities\Entity;
use Modules\Organisation\Entities\Holding;

class OrganisationTest extends TestCase
{
    /**
     * @test
     */
    public function unauthorized_user_cannot_create_organisation()
    {
        $user = User::factory()->create();
        $response = $this->post(route('organisation.store'), [
            'name' => 'mas',
            'npwp' => 'mas',
            'note' => 'mas',
            'holding_id' => 'mas',
            'entity_id' => 'mas',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function organisation_store()
    {
        $holding = Holding::factory()->create();
        $entity = Entity::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('organisation.store'), [
                'name' => 'mas',
                'npwp' => 'mas',
                'note' => 'mas',
                'holding_id' => $holding->id,
                'entity_id' => $entity->id,
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
        $permission = Permission::firstOrCreate(['name' => 'crud holding-organisation']);
        $user->givePermissionTo(Permission::all());

        return $user;
    }
}
