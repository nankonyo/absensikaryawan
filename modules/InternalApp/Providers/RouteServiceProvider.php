<?php

namespace Modules\InternalApp\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\InternalApp\Http\Controllers';

    public function map(): void
    {
        Route::middleware(config('internalapp.middleware', ['web', 'auth']))
            ->prefix(config('internalapp.prefix', 'internal'))
            ->name('internal.')
            ->namespace($this->moduleNamespace)
            ->group(base_path('modules/InternalApp/Routes/web.php'));
    }
}
