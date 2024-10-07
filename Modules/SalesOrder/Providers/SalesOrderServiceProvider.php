<?php

namespace Modules\SalesOrder\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\SalesOrder\Entities\LogSalesOrderOrigin;
use Modules\SalesOrder\Observers\SalesOrderObserver;
use Modules\SalesOrder\Console\OrderTotalSyncCommand;
use Modules\SalesOrder\Console\OrderGradingSyncCommand;
use Modules\SalesOrder\Console\SalesOrderModeSetUpCoimmand;
use Modules\SalesOrder\Console\SalesOrderStatusFeeShouldCommand;
use Modules\SalesOrderV2\Observers\SalesOrderV2NotificationObserver;
use Modules\SalesOrder\Console\SalesOrderStatusFeeUnmatchCheckCommand;
use Modules\SalesOrder\Console\FeeRegulerSharingOriginGeneratorCommand;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOriginFromOrderDetailAction;

class SalesOrderServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'SalesOrder';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'salesorder';

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

        /** */
        $this->commands([
            FeeRegulerSharingOriginGeneratorCommand::class,
            SalesOrderStatusFeeUnmatchCheckCommand::class,
            SalesOrderStatusFeeShouldCommand::class,
            SalesOrderModeSetUpCoimmand::class,
            OrderGradingSyncCommand::class,
            OrderTotalSyncCommand::class,
        ]);
        SalesOrder::observe(SalesOrderV2NotificationObserver::class);
        SalesOrder::observe(SalesOrderObserver::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(GenerateSalesOriginFromOrderDetailAction::class, function ($app) {
            return new GenerateSalesOriginFromOrderDetailAction(
                $app->make(SalesOrder::class),
                $app->make(AdjustmentStock::class),
                $app->make(SalesOrderOrigin::class),
                $app->make(LogSalesOrderOrigin::class)
            );
        });
        $this->app->singleton(GenerateSalesOrderOriginFromMemoAction::class, function ($app) {
            return new GenerateSalesOrderOriginFromMemoAction(
                $app->make(SalesOrderOrigin::class),
            );
        });
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
            $sourcePath => $viewPath,
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
