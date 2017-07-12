<?php

namespace App\Providers;

use App\TransactionManager;
use Illuminate\Support\ServiceProvider;
use App\Contracts\TransactionRepositoryInterface;

class TransactionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TransactionManager::class, function ($app) {
            return new TransactionManager;
        });
    }
}
