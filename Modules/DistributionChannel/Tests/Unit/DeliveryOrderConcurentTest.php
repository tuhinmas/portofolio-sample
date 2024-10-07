<?php

use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

// test("delivery order, race condition test", function () {
//     $dispatch_order = DispatchOrder::factory()->create();
//     $url1 = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
//         "dispatch_order_id" => $dispatch_order->id,
//         "date_delivery" => now(),
//         "is_promotion" => false,
//     ]);
   
//     $url2 = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
//         "dispatch_order_id" => $dispatch_order->id,
//         "date_delivery" => now(),
//         "is_promotion" => false,
//     ]);


//     // Use the Guzzle HTTP client to make API requests
//     $client = new Client();

//     // Use the Concurrent package to execute requests concurrently
//     $responses = Concurrent::execute([
//         function () use ($client, $url1) {
//             return $client->get($url1);
//         },
//         function () use ($client, $url2) {
//             return $client->get($url2);
//         },
//     ]);

//     // Extract the response bodies from the Guzzle responses
//     $response1 = json_decode($responses[0]->getBody()->getContents(), true);
//     $response2 = json_decode($responses[1]->getBody()->getContents(), true);

//     // Assert the expected results
//     $this->assertEquals(['data' => 'response1'], $response1);
//     $this->assertEquals(['data' => 'response2'], $response2);
// });
