<?php

namespace Modules\Personel\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\Marketing;
use Illuminate\Database\Eloquent\Factory;
use Modules\Personel\Observers\PersonelObserver;
use Modules\Personel\Observers\MarketingObserver;
use Modules\Personel\Console\MarketingSyncDataCommand;
use Modules\Personel\Console\OrderReturnMarkerCommand;
use Modules\Personel\Console\FeeSharingGeneartorComand;
use Modules\Personel\Console\PersonelSupervisorHistoriesSync;
use Modules\Personel\Console\PointMarketingCalculatorCommand;
use Modules\Personel\Console\fee\MarketingFeeCounterV2Command;
use Modules\Personel\Console\fee\MarketingFeeCounterV3Command;
use Modules\Personel\Console\MarketingFeeTargetCounterCommand;
use Modules\Personel\Console\MarketingFeeRegulerCounterCommand;
use Modules\Personel\Console\PointMarketingCalculatorV2Command;
use Modules\Personel\Console\History\MarketingStatusHistoryFixingCommand;

class PersonelServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Personel';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'personel';

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

        /**
         * custom command register
         */
        $this->commands([
            MarketingStatusHistoryFixingCommand::class,
            MarketingFeeRegulerCounterCommand::class,
            PointMarketingCalculatorV2Command::class,
            PointMarketingCalculatorCommand::class,
            PersonelSupervisorHistoriesSync::class,
            MarketingFeeCounterV2Command::class,
            MarketingFeeCounterV3Command::class,
            FeeSharingGeneartorComand::class,
            OrderReturnMarkerCommand::class,
            MarketingSyncDataCommand::class,
        ]);

        /**
         * observer
         *
         * @return void
         */
        Personel::observe(PersonelObserver::class);
        Marketing::observe(MarketingObserver::class);
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
