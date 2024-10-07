<?php

use Modules\Authentication\Entities\MobileVersion;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * CREATE
 */
test("can create with valid data", function () {
    $response = actingAsSupport()->postJson("/api/auth/v1/mobile-version", [
        "version" => "1.0.02",
        "environment" => "staging",
        "note" => "test link",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/mobile/apk/staging/APK+Share.pdf",
    ]);

    $response->assertStatus(201);
});

test("can not create with same version", function () {
    $old_version = MobileVersion::factory()->create();
    $response = actingAsSupport()->postJson("/api/auth/v1/mobile-version", [
        "version" => $old_version->version,
        "environment" => "staging",
        "note" => "test link",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/mobile/apk/staging/APK+Share.pdf",
    ]);
    $response->assertStatus(422);
});

test("can not create with invalid link", function () {
    $response = actingAsSupport()->postJson("/api/auth/v1/mobile-version", [
        "version" => "00.00.00",
        "environment" => "staging",
        "note" => "test link",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/mobile/apk/xxx/APK+Share.pdf",
    ]);
    $response->assertStatus(422);
});
