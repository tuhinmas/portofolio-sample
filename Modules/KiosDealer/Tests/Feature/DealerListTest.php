<?php

namespace Modules\KiosDealer\Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Illuminate\Testing\Fluent\AssertableJson;

class DealerListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_dealer_list_without_login_first()
    {
        $response = $this->getJson('/api/v1/dealer/dealer');
        $response->assertStatus(401);
    }

    public function test_dealer_list_support(){
        $user = User::where("name", "support")->first();
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/dealer/dealer');
        $response->assertStatus(200);
    }

    public function test_dealer_list_supervisor(){
        $user = User::where("name", "personelrmc1@mail.com")->first();
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/dealer/dealer', [
            "scope_supervisor" => true
        ]);
        $response->assertStatus(200);
    }

    public function test_delaerlist_staff(){
        $user = User::where("name", "personelrmc1@mail.com")->first();
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/dealer/dealer', [
            "personel_id" => $user->personel_id
        ]);
        $response->assertStatus(200);
    }
}
