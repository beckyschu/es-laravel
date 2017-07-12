<?php

namespace App\Providers;

use App\AuthStore;
use App\Models\Discovery;
use Illuminate\Support\ServiceProvider;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\AccountRepositoryInterface;
use App\Contracts\CrawlerRepositoryInterface;

class AppServiceProvider extends ServiceProvider
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
        $this->app->bind('App\Contracts\AccountRepositoryInterface', 'App\Repositories\Eloquent\AccountRepository');

        $this->app->bind('App\Contracts\UserRepositoryInterface', 'App\Repositories\Eloquent\UserRepository');

        $this->app->bind('App\Contracts\AssetRepositoryInterface', 'App\Repositories\Eloquent\AssetRepository');

        $this->app->bind('App\Contracts\DiscoveryRepositoryInterface', 'App\Repositories\Eloquent\DiscoveryRepository');

        $this->app->bind('App\Contracts\SellerRepositoryInterface', 'App\Repositories\Eloquent\SellerRepository');

        $this->app->bind('App\Contracts\ReportRepositoryInterface', 'App\Repositories\Eloquent\ReportRepository');

        $this->app->bind('App\Contracts\TransactionRepositoryInterface', 'App\Repositories\Eloquent\TransactionRepository');

        $this->app->bind('App\Contracts\CrawlerRepositoryInterface', 'App\Repositories\Eloquent\CrawlerRepository');

        $this->app->bind('App\Contracts\CrawlRepositoryInterface', 'App\Repositories\Eloquent\CrawlRepository');

        $this->app->bind('App\Contracts\SubmissionRepositoryInterface', 'App\Repositories\Eloquent\SubmissionRepository');

        $this->app->bind('App\Contracts\EnforcerRepositoryInterface', 'App\Repositories\Eloquent\EnforcerRepository');

        $this->app->bind('App\Contracts\ScanRepositoryInterface', 'App\Repositories\Eloquent\ScanRepository');

        $this->app->bind('App\Contracts\ImportRepositoryInterface', 'App\Repositories\Eloquent\ImportRepository');

        //Bind Auth singleton
        $this->app->singleton(AuthStore::class, function ($app) {
            return new AuthStore(
                $app[UserRepositoryInterface::class],
                $app[CrawlerRepositoryInterface::class],
                $app[AccountRepositoryInterface::class]
            );
        });
    }
}
