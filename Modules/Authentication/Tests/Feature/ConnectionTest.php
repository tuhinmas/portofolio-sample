<?php

namespace Modules\KiosDealer\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * connection test
     */
    public function test_conection_DB()
    {
        $response = DB::connection()->getConfig();
        dump($response);
    }

    public function test_login_structure()
    {
        $response = $this->postJson("/api/auth/v2/login", [
            "login" => "support@mail.com",
            "password" => "password",
        ]);
        $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) =>
                $json
                    ->hasAll([
                        "response_code",
                        "response_msg",
                        "data",
                        "link",
                    ])
                    ->has("data", fn($json) =>
                        $json->hasAny([
                            "token",
                            "active",
                            "active_requirement",
                            "user"
                        ])
                    )
            );
    }
}
