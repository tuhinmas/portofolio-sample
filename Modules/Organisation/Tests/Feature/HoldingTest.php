<?php

namespace Modules\Organisation\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use Modules\Organisation\Entities\Holding;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HoldingTest extends TestCase
{
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_Holding(){
        $user = User::factory()->create();
        $response = $this->get(route('holding.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_Holding(){
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
    public function holding_index(){
        $response = $this->actingAs($this->user())
            ->get(route('holding.index'));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_store()
    {
        $response = $this->actingAs($this->user())
            ->post(route('holding.store'), [
                'name' => 'Javamas',
                'tanggal_berdiri' => '2020-03-12',
                'note' => 'javamas adalah'
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_edit()
    {
        $holding = Holding::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('holding.edit', [
                'holding' => $holding->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_update()
    {
        $holding = Holding::factory()->create();
        $response = $this->actingAs($this->user())
            ->put(route('holding.update', [
                'holding' => $holding->id,
            ]), [
                'name' => 'Javamas',
                'tanggal_berdiri' => '2020-03-12',
                'note' => 'javamas adalah'
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function holding_delete()
    {
        $holding = Holding::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('holding.destroy', [
                'holding' => $holding->id])
            );
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @return void
     */
    public function user(){
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'crud holding-organisation']);
        $user->givePermissionTo(Permission::all());

        return $user;
    }
}
