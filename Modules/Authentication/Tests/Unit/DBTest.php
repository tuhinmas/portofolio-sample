<?php

uses(Tests\TestCase::class);

test("DB test info", function () {
    $response = DB::connection()->getConfig();
    dump($response);

    $this->assertTrue(true);
});