<?php

namespace Modules\KiosDealer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Database\Eloquent\Factory;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Observers\DealerObserver;
use Modules\KiosDealer\Observers\StoreTempObserver;
use Modules\KiosDealer\Observers\DealerTempObserver;
use Modules\KiosDealer\Console\DealerGradeResetCommand;
use Modules\KiosDealer\Observers\SubDealerTempObserver;
use Modules\KiosDealer\Console\StoreDraftCleanUpCommand;
use Modules\KiosDealer\Console\SyncAddressWithAreaCommand;
use Modules\KiosDealer\Console\StatusFeeDealerFixingCommand;
use Modules\KiosDealer\Console\SubDealerToDealerTransferCommand;
use Modules\KiosDealer\Console\SubDealerAddressDoentHaveAddressCommand;

class KiosDealerServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'KiosDealer';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'kiosdealer';

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
        $this->commands([
            SubDealerAddressDoentHaveAddressCommand::class,
            SubDealerToDealerTransferCommand::class,
            StatusFeeDealerFixingCommand::class,
            SyncAddressWithAreaCommand::class,
            StoreDraftCleanUpCommand::class,
            DealerGradeResetCommand::class,
        ]);
        SubDealerTemp::observe(SubDealerTempObserver::class);
        StoreTemp::observe(StoreTempObserver::class);
        DealerTemp::observe(DealerTempObserver::class);
        Dealer::observe(DealerObserver::class);
        DealerV2::observe(DealerObserver::class);
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
