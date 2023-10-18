<?php

namespace H2o\ComplexSearch;

use H2o\ComplexSearch\Paginator\Paginator;
use Illuminate\Support\ServiceProvider;

class ComplexSearchServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot()
    {
    }

    /**
     * Register all modules.
     */
    public function register()
    {
        $this->app->bind('Illuminate\Pagination\LengthAwarePaginator', Paginator::class);
    }
}
