<?php

use Illuminate\Support\Facades\Http;

if (!function_exists("telegram_notiication")) {
    function telegram_notiication($message)
    {
        $apiURL = 'https://api.telegram.org/bot5596600944:AAFVBiNpzPTIoMzny9cEL8NfjrRUPQa1u4M/sendMessage';
        $content = [
            "chat_id" => "-615892060",
            'text' => $message,
            "disable_notification" => false,
        ];

        $headers = [
            "Content-Type" => "application/json",
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $content);
        $response_body = json_decode($response->getBody(), true);
        return $response_body;
    }
}
