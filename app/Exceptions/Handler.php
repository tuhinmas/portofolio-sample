<?php

namespace App\Exceptions;

use App\Traits\ResponseHandler;
use ErrorException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use PDOException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ResponseHandler;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'unauthenticated user',
                "data" => self::environmentResponse($e, $request),
            ], 401);
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'model not found.',
                "data" => self::environmentResponse($e, $request),
            ], 404);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'failed, not found.',
                "data" => self::environmentResponse($e, $request),
            ], 404);
        });

        $this->renderable(function (ValidationException $e, $request) {
            $data = $e->errors();
            $data["request_id"] = $request?->attributes->get('request_id');
            return response()->json([
                'response_code' => '04',
                'response_message' => 'invalid data send',
                "data" => $e->errors(),
                "data" => $data,
            ], 422);
        });

        $this->renderable(function (ErrorException $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'error BE',
                "data" => self::environmentResponse($e, $request),
            ], 500);
        });

        $this->renderable(function (PDOException $e, $request) {
            return response()->json([
                'response_code' => '04',
                'response_message' => 'invalid data send',
                "data" => self::environmentResponse($e, $request),
            ], 422);
        });

        $this->renderable(function (\BadMethodCallException $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'error BE',
                "data" => self::environmentResponse($e, $request),
            ], 500);
        });

        $this->renderable(function (AccessDeniedHttpException $e, $request) {
            return response()->json([
                'response_code' => '05',
                'response_message' => 'unauthorized action',
                "data" => self::environmentResponse($e, $request),
            ], 403);
        });

        $this->renderable(function (\Exception $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'error BE',
                "data" => self::environmentResponse($e, $request),
            ], 500);
        });

        $this->renderable(function (\TypeError $e, $request) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'error BE',
                "data" => self::environmentResponse($e, $request),
            ], 500);
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return $this->response("01", "unauthenticated user", "please login first", 401);
        }

        return redirect()->guest('login');
    }

    public static function environmentResponse($exception, $request = null)
    {
        $message = !empty($exception->getMessage()) ? $exception->getMessage() : "Route not found";

        return match (true) {
            app()->environment("production") => [
                "message" => $message,
                "request_id" => $request?->attributes->get('request_id'),
            ],
            default => [
                "message" => $message,
                "request_id" => $request?->attributes->get('request_id'),
                "line" => $exception->getLine(),
                "file" => $exception->getFile(),
                "trace" => $exception->getTrace(),
            ],
        };
    }
}
