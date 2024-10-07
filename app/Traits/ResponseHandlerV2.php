<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Throwable;

trait ResponseHandlerV2
{
    public function response($code, $message, $data, $http_code = Response::HTTP_OK)
    {
        if ($data instanceof ModelNotFoundException) {
            $data = self::environmentResponse($data);
            $http_code = 404;
        } elseif ($data instanceof Throwable) {
            $data = self::environmentResponse($data);
            $http_code = 500;
        }

        return response()
            ->json([
                'response_code' => $code,
                'response_message' => $message,
                'data' => $data,
            ])
            ->setStatusCode($http_code);
    }

    public static function environmentResponse($exception)
    {
        return match (true) {
            app()->environment("production") => [
                "message" => $exception->getMessage(),
                "request_id" => app("request")->attributes->get("request_id"),
            ],
            default => [
                "message" => $exception->getMessage(),
                "request_id" => app("request")->attributes->get("request_id"),
                "line" => $exception->getLine(),
                "file" => $exception->getFile(),
                "trace" => $exception->getTrace(),
            ],
        };
    }
}
