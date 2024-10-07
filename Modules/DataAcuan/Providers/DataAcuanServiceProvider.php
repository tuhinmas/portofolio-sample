<?php

namespace Modules\DataAcuan\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Events\FeeInCreatedFeeProductEvent;
use Modules\DataAcuan\Events\FeeInDeletedFeeProductEvent;
use Modules\DataAcuan\Console\FeeProductDuplicatorCommand;
use Modules\DataAcuan\Events\UpdatedFeeTargetProductEvent;
use Modules\DataAcuan\Observers\MarketingAreaDistrictObserver;
use Modules\DataAcuan\Events\FeeInCreatedFeeTargetProductEvent;
use Modules\DataAcuan\Events\FeeInDeletedFeeTargetProductEvent;
use Modules\DataAcuan\Listeners\FeeInCreatedFeeProductListener;
use Modules\DataAcuan\Listeners\FeeInDeletedFeeProductListener;
use Modules\DataAcuan\Listeners\UpdatedFeeTargetProductListener;
use Modules\DataAcuan\Events\FeeWhenFeePositionPercentageChangeEvent;
use Modules\DataAcuan\Listeners\FeeInCreatedFeeTargetProductListener;
use Modules\DataAcuan\Listeners\FeeInDeletedFeeTargetProductListener;
use Modules\DataAcuan\Console\SyncRetailerAndDistributorToMarketingCommand;
use Modules\DataAcuan\Listeners\FeeWhenFeePositionPercentageChangeListener;

class DataAcuanServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'DataAcuan';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'dataacuan';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        /* on updated fee reguler product */
        $this->app['events']->listen(FeeWhenFeePositionPercentageChangeEvent::class, FeeWhenFeePositionPercentageChangeListener::class);

        /* on updated fee target product */
        $this->app['events']->listen(UpdatedFeeTargetProductEvent::class, UpdatedFeeTargetProductListener::class);
        
        /* on deleted fee reguler product */
        $this->app['events']->listen(FeeInDeletedFeeProductEvent::class, FeeInDeletedFeeProductListener::class);
        
        /* on deleted fee target product */
        $this->app['events']->listen(FeeInDeletedFeeTargetProductEvent::class, FeeInDeletedFeeTargetProductListener::class);

        /* on created new fee reguler product */
        $this->app["events"]->listen(FeeInCreatedFeeProductEvent::class, FeeInCreatedFeeProductListener::class);

        /* on created new fee target product */
        $this->app["events"]->listen(FeeInCreatedFeeTargetProductEvent::class, FeeInCreatedFeeTargetProductListener::class);

        $this->commands([
            FeeProductDuplicatorCommand::class,
            SyncRetailerAndDistributorToMarketingCommand::class
        ]);

        MarketingAreaDistrict::observe(MarketingAreaDistrictObserver::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
