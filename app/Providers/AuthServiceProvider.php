<?php

namespace App\Providers;

use App\Models\LogRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Authentication\Entities\LoginLog;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // ResetPassword::createUrlUsing(function ($user, string $token) {
        //     return 'http://localhost:8000/password/reset-password/' . $token . '/' . $user->email;
        // });

        if (auth()->check()) {

            /**
             * Login Log
             */
            LoginLog::updateOrCreate([
                "user_id" => auth()->id(),
                "date" => Carbon::now()->format("Y-m-d"),
                "login_at" => auth()->user()->last_login_at,
            ], [
                "token" => substr(auth()->tokenById(auth()->id()), -20),
            ]);

            /* log */
            $except_keys = array_keys($this->app->request->file());
            if (empty(env("LOG_REQUEST")) || env("LOG_REQUEST") == true) {
                $log = LogRequest::create([
                    "environment" => env("APP_ENV", "local"),
                    "user_id" => auth()->id() ?: null,
                    "request" => [
                        "method" => $this->app->request->getMethod(),
                        "body" => match (true) {
                            !$this->app->request->isJson() => $this->app->request->except($except_keys),
                            default => $this->app->request->all(),
                        },
                        "http_response" => app('Illuminate\Http\Response')->status(),
                    ],
                    "route" => $this->app->request->getRequestUri(),
                    "http_code" => app('Illuminate\Http\Response')->status(),
                    "user_agent" => $this->app->request->header('user-agent'),
                ]);
    
                $this->app->request->attributes->set('request_id', $log->id);
            }
        }

    }
}
