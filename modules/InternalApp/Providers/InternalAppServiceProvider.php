<?php

namespace Modules\InternalApp\Providers;

use Illuminate\Support\ServiceProvider;

class InternalAppServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'InternalApp';

    protected string $moduleNameLower = 'internalapp';

    public function boot(): void
    {
        $this->loadViewsFrom(base_path('modules/InternalApp/Resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(base_path('modules/InternalApp/Resources/lang'), $this->moduleNameLower);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(base_path('modules/InternalApp/Config/config.php'), $this->moduleNameLower);

        $this->app->register(RouteServiceProvider::class);
    }
}
