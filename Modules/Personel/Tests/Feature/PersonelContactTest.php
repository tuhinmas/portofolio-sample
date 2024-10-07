<?php

namespace Modules\Personel\Tests\Feature;

use App\Models\Contact;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Personel\Entities\Personel;
use Tests\TestCase;

class PersonelContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_personel_contact()
    {
        $response = $this->get(route('personel-contact.index'));
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
    /**
     * @test
     */
    public function unauthorized_user_cannot_access_personel_contact()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('personel-contact.index'));
        $response->assertStatus(403);
    }

    /**
     *
     *
     * @test
     */
    public function personel_contact_index()
    {
        $personel = personel::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('personel-contact.index',
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
    public function personel_contact_store()
    {
        $personel = Personel::factory()->create();
        $response = $this->actingAs($this->user())
            ->post(route('personel-contact.store'), [
                'personel_id' => $personel->id,
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
    public function personel_contact_edit()
    {
        $contact = Contact::factory()->create();
        $response = $this->actingAs($this->user())
            ->get(route('personel-contact.edit', [
                'personel_contact' => $contact->id,
            ]));
        $response->assertStatus(200);
    }

    /**
     *
     *
     * @test
     */
    public function personel_contact_update()
    {
        $contact = Contact::factory()->create();
        $response = $this->actingAs($this->user())
            ->put(route('personel-contact.update', [
                'personel_contact' => $contact->id,
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
    public function personel_contact_delete()
    {
        $contact = Contact::factory()->create();
        $response = $this->actingAs($this->user())
            ->delete(route('personel-contact.destroy', [
                'personel_contact' => $contact->id,
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
