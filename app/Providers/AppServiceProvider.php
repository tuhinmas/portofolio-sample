<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\RequestMerger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\ParallelTesting;

class AppServiceProvider extends ServiceProvider
{
    use RequestMerger;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->request->has("include")) {
            $include = $this->includeRequestMerger($this->app->request->include);

            $this->app->request->merge([
                "includes" => $include,
            ]);
        }

        /* latitude merger */
        if ($this->app->request->has("latitude")) {
            $this->app->request->merge([
                "latitude" => Str::replace(',', '.', $this->app->request->latitude),
            ]);
        }

        /* longitude merger */
        if ($this->app->request->has("longitude")) {
            $this->app->request->merge([
                "longitude" => Str::replace(',', '.', $this->app->request->longitude),
            ]);
        }
        $guarded = [
            "id",
            "created_at",
            "updated_at",
            "deleted_at",
        ];
        $this->app->request->replace(
            collect($this->app->request->all())->except($guarded)->toArray()
        );

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ParallelTesting::setUpProcess(function ($token) {
            // ...
        });
 
        ParallelTesting::setUpTestCase(function ($token, $testCase) {
            // ...
        });
 
        // Executed when a test database is created...
        ParallelTesting::setUpTestDatabase(function ($database, $token) {
        });
 
        ParallelTesting::tearDownTestCase(function ($token, $testCase) {
            // ...
        });
 
        ParallelTesting::tearDownProcess(function ($token) {
            // ...clear
            
        });
        
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');
        Model::preventLazyLoading(
            app()->isLocal()
        );
    }
}
