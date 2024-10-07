<?php

namespace App\Http\Middleware;

use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Route;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Authenticate extends Middleware
{
    use ResponseHandlerV2;

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);
        if (auth()->check() && auth()->user()->password_change_at && $request->bearerToken()) {
            $iat = JWTAuth::setToken($request->bearerToken())->getPayload()->get("iat");
            if (auth()->user()->password_change_at) {
                $diff_in_second = Carbon::createFromTimestamp($iat)->diffInSeconds(auth()->user()->password_change_at, false);
                if ($diff_in_second > 0) {
                    $response = $this->response("05", "test", "test");
                    throw new AuthorizationException('invalid token, password has change');
                }
            }

        }

        return $next($request);
    }
}
