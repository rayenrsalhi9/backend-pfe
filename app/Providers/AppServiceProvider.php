<?php

namespace App\Providers;

use App\Repositories\Contracts\ResponsesAuditRepositoryInterface;
use App\Repositories\Implementation\ResponsesAuditRepository;
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
        $this->app->bind(ResponsesAuditRepositoryInterface::class, ResponsesAuditRepository::class);
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
