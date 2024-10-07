<?php

namespace Tests\Feature;

use JWTAuth;
use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * login test
     *
     * @test
     */
    public function login_test()
    {
        $user = User::factory()->create([
            'email' => 'administrator@mail.com',
            'password' => bcrypt('password'),
        ]);
        $response = $this->postJson(route('loginAPI'),
            [
                'email' => $user->email,
                'password' => 'password',
            ],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'response_code' => '00',
        ]);
    }

    /**
     * if user not found
     *
     * @test
     */
    public function user_not_found()
    {
        $user = User::factory()->create();
        $response = $this->postJson(route('loginAPI'),
            [
                'email' => 'adama@gmail.com',
                'password' => 'password',
            ],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'response_code' => '01',
        ]);
    }

    /**
     * logout test
     *
     * @test
     */
    public function logout()
    {
        $response = $this->postJson(route('logoutAPI'), [], $this->authorize());
        $response->assertStatus(200);
        $response->assertJson([
            'response_code' => '00',
        ]);
    }

    /**
     * logout failed
     *
     * @test
     */
    public function logout_failed()
    {
        $authorize = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'token not valid',
        ];

        $response = $this->postJson(route('logoutAPI'), [], $authorize);
        $response->assertStatus(401);
    }

    /**
     * user initial set up for testing
     *
     * @return void
     */
    public function user()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'super-admin']);
        $permission = Permission::create(['name' => 'show administrator permission']);
        $role->givePermissionTo(Permission::all());
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * generate jwt token
     *
     * @param [type] $user
     * @return void
     */
    public function token($user)
    {
        $token = JWTAuth::fromUser($user);
        return $token;
    }

    public function authorize()
    {
        $authorize = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token($this->user()),
        ];

        return $authorize;
    }
}
