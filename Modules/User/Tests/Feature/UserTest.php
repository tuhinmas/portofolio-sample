<?php

namespace Modules\User\Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Profile\Entities\Profile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * only user wit edit article permision can see users list
     *
     * @test
     */
    public function only_user_with_edit_article_permission_can_see_users_list()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route("users.list"));
        $response->assertStatus(403);
    }

    /**
     * user with edit article permission can see user list
     *
     * @test
     */
    public function user_with_edit_article_permission_can_see_user_list()
    {
        $response = $this->actingAs($this->user())->get(route('users.list'));
        $response->assertStatus(200);
    }

    /**
     * create new user test
     *
     * @test
     */
    public function only_user_with_edit_article_permission_can_create_new_user()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('user.create'));
        $response->assertStatus(403);
    }

    /**
     * user ccan create new user
     *
     * @test
     */
    public function user_with_edit_article_permission_can_create_new_user()
    {
        $response = $this->actingAs($this->user())->get(route('user.create'));
        $response->assertSeeText('roles', 'position');
    }

    /**
     * store new user data to database
     *
     * @test
     */
    public function store_new_user_to_database()
    {
        $position = Position::create([
            'position' => 'staff',
            'salary' => '8',
        ]);

        $credensial = [
            '_token' => csrf_token(),
            'name' => 'Hari Sungkawa',
            'email' => 'haris@gmail.com',
            'password' => '1sampai8',
            'password_confirmation' => '1sampai8',
            'hoby' => 'kerja',
            'address' => 'mbuh_adoh',
            'hp' => '0851231231',
            'position' => $position->id,
        ];
        $response = $this->actingAs($this->user())->post(route("user.store"), $credensial);

        $response->assertStatus(302);
        $response->assertRedirect(route('users.list'));
    }

    /**
     * edit profile users
     *
     * @test
     */
    public function user_with_permission_edit_article_can_edit_profile_user_account()
    {
        $position = Position::create([
            'position' => 'manager',
            'salary' => '8',
        ]);

        $user = User::factory()->create();
        $profile = Profile::create([
            '_token' => csrf_token(),
            'name' => 'Hari Sungkawa',
            'hoby' => 'kerja',
            'address' => 'mbuh_adoh',
            'hp' => '0851231231',
            'user_id' => $user->id,
            'position_id' => $position->id,
        ]);
        $response = $this->actingAs($this->user())
            ->get(route('user.edit', ['id' => $profile->id]));
        $response->assertStatus(200);
    }

    /**
     * update detail profile
     *
     * @test
     */
    public function update_profile()
    {
        Role::create(['name' => 'admin']);
        $position = Position::create([
            'position' => 'manager',
            'salary' => '8',
        ]);

        $user = User::factory([
            'id' => 1,
        ])->create();

        $profile = Profile::create([
            'id' => 1,
            '_token' => csrf_token(),
            'name' => 'Hari Sungkawa',
            'hoby' => 'kerja',
            'address' => 'mbuh_adoh',
            'hp' => '0851231231',
            'user_id' => $user->id,
            'position_id' => $position->id,
        ]);
        $response = $this->actingAs($this->user())
            ->put(route('user.update', ['id' => $profile->id]), [
                '_token' => csrf_token(),
                'email' => 'haris@gmail.com',
                'password' => '1sampai8',
                'password_confirmation' => '1sampai8',
                'name' => 'Hari Sungkawa',
                'hoby' => 'kerja kerja kerja',
                'address' => 'mbuh_adoh',
                'hp' => '0851231231',
                'user_id' => $user->id,
                'position' => $position->id,
                'role' => 'admin',
            ]);
        $response->assertRedirect(route('users.list'));
    }

    /**
     * delete user
     *
     * @test
     */
    public function delete_user()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('user.delete', ['id' => $user->id]));
        $response->assertStatus(302);
        $response->assertRedirect(route('users.list'));
    }

    /**
     * edit permission
     *
     * @test
     */
    public function edit_permission()
    {
        Role::create(['name' => 'admin']);
        $position = Position::create([
            'position' => 'manager',
            'salary' => '8',
        ]);

        $user = $this->user();
        $profile = Profile::create([
            'id' => 1,
            '_token' => csrf_token(),
            'name' => 'Hari Sungkawa',
            'hoby' => 'kerja',
            'address' => 'mbuh_adoh',
            'hp' => '0851231231',
            'user_id' => $user->id,
            'position_id' => $position->id,
        ]);

        $permissions = Permission::all();

        $response = $this->actingAs($this->user())
            ->get(route('user.edit_permission', ['id' => $user->id]));
        $response->assertStatus(200);
    }

    /**
     * add permision to user
     *
     * @test
     */
    public function update_permission()
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'read your mind']);
        $response = $this->actingAs($this->user())
            ->post(route("user.update.permission", ['id' => $user->id]), [
                'permission' => $permission->name,
            ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('users.list'));
    }
    /**
     *user with permission
     *
     * @return void
     */
    public function user()
    {
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'edit article']);

        $user->givePermissionTo(Permission::all());

        return $user;
    }

    /**
     * logged in user
     */
    public function user_Logged_in()
    {
        $user = User::factory()->create();
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertAuthenticated();
        return $user;
    }
}
