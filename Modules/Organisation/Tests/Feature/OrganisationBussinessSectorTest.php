<?php

namespace Modules\Organisation\Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataAcuan\Entities\BussinessSector;
use Modules\Organisation\Entities\Organisation;
use Tests\TestCase;

class OrganisationBussinessSectorTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_bussiness_sector()
    {
        $user = User::factory()->create();
        $response = $this->get(route('organisation-bussiness-sector.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_bussiness_sector()
    {
        $user = User::factory()->create();
        $response = $this->get(route('organisation-bussiness-sector.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function bussiness_sector_index()
    {
        $organisation = Organisation::factory()->create();
        $bussiness_sector = BussinessSector::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('organisation-bussiness-sector.index',
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
    public function bussiness_sector_store()
    {
        $organisation = Organisation::factory()->create();
        $bussiness_sector = BussinessSector::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('organisation-bussiness-sector.store'), [
                'bussiness_sector' => $bussiness_sector->id,
                'organisation_id' => $organisation->id,
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function bussiness_sector_edit()
    {
        $bussiness_sector = BussinessSector::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('organisation-bussiness-sector.edit', [
                'organisation_bussiness_sector' => $bussiness_sector->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function bussiness_sector_update()
    {
        $bussiness_sector = BussinessSector::factory()->create();
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->put(route('organisation-bussiness-sector.update', [
                'organisation_bussiness_sector' => $organisation->id,
            ]), [
                'bussiness_sector' => $bussiness_sector->id,
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function bussiness_sector_delete()
    {
        $organisation = Organisation::factory()->create();
        $bussiness_sector = BussinessSector::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('organisation-bussiness-sector.destroy', [
                'organisation_bussiness_sector' => $organisation->id]),[
                    'bussiness_sector' => $bussiness_sector->id
                ]
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
