<?php

use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
 */

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
 */

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
 */

function something()
{
    // ..
}

/**
 * Set the currently logged in user for the application.
 *
 * @return TestCase
 */
function actingAsSupport(string $driver = null, $permissions = null)
{
    $user = User::where("name", "support")->first();
    if ($permissions) {
        $user->givePermissionTo($permissions);
    }
    return test()->actingAs($user, $driver);
}

function actingAsSupportFrom(string $personel_id = null, $user = null)
{
    if ($user) {
        return test()->actingAs($user);
    }

    $user = User::factory()->create([
        "personel_id" => $personel_id ?? Personel::factory()->create()->id,
        "password" => bcrypt("password"),
    ]);

    return test()->actingAs($user);
}

function actingAsMarketing(string $driver = null, $personel_id = null)
{
    $user = User::query()
        ->when($personel_id, function ($QQQ) use ($personel_id) {
            return $QQQ->where("personel_id", $personel_id);
        })
        ->when(!$personel_id, function ($QQQ) {
            return $QQQ->whereHas("profile", function ($QQQ) {
                return $QQQ
                    ->whereHas("salesOrder")
                    ->whereHas("position", function ($QQQ) {
                        return $QQQ->whereIn("name", marketing_positions());
                    });
            });

        })
        ->first();

    if (!$user) {
        $user = User::factory()->create([
            "personel_id" => $personel_id ?? Personel::factory()->create()->id,
            "password" => bcrypt("password"),
        ]);
    } else {
        $user->password = bcrypt("password");
        $user->save();
    }

    return test()->actingAs($user, $driver);
}

function actingAsApplicator(string $driver = null)
{
    $applicator_position = Position::query()
        ->where("name", "Aplikator")
        ->first();

    $personel = Personel::factory()->create([
        "position_id" => $applicator_position->id,
    ]);

    $user = User::factory()->create([
        "personel_id" => $personel->id,
    ]);

    $user->assignRole("Aplikator");
    return test()->actingAs($user, $driver);
}
