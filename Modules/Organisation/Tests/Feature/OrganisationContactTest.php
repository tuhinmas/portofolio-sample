<?php

namespace Modules\Organisation\Tests\Feature;

use App\Models\Contact;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organisation\Entities\Organisation;
use Tests\TestCase;

class OrganisationContactTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_organisation_contact()
    {
        $user = User::factory()->create();
        $response = $this->get(route('organisation-contact.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_organisation_contact()
    {
        $user = User::factory()->create();
        $response = $this->get(route('organisation-contact.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     *
     *
     * @test
     */
    public function organisation_contact_index()
    {
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('organisation-contact.index',
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
    public function oragnisation_contact_store()
    {
        $organisation = Organisation::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('organisation-contact.store'), [
                'organisation_id' => $organisation->id,
                'contact_type' => 'telephone',
                'coontact_detail' => '12312131213',
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function organisation_contact_edit()
    {
        $contact = Contact::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('organisation-contact.edit', [
                'organisation_contact' => $contact->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function organisation_contact_update()
    {
        $contact = Contact::factory()->create();
        $response = $this->actingAs($this->user())
            ->put(route('organisation-contact.update', [
                'organisation_contact' => $contact->id,
            ]), [
                'contact_type' => 'telephone',
                'coontact_detail' => '12312131213',
            ]);
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function organisation_contact_delete()
    {
        $contact = Contact::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('organisation-contact.destroy', [
                'organisation_contact' => $contact->id,
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
