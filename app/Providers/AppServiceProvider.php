<?php

namespace App\Providers;

use App\Repositories\Interfaces\ResponseAuditTrailRepositoryInterface;
use App\Repositories\ResponseAuditTrailRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind Response Audit Trail Repository
        $this->app->bind(ResponseAuditTrailRepositoryInterface::class, ResponseAuditTrailRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       
    }
}
