<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\KeyManagementService;
use App\Services\EncryptionService;
use App\Services\PasswordService;
use App\Services\CredentialCheckService;
use App\Services\MACService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register security services as singletons
        $this->app->singleton(KeyManagementService::class, function ($app) {
            return new KeyManagementService();
        });
        
        $this->app->singleton(EncryptionService::class, function ($app) {
            return new EncryptionService($app->make(KeyManagementService::class));
        });
        
        $this->app->singleton(PasswordService::class, function ($app) {
            return new PasswordService();
        });
        
        $this->app->singleton(MACService::class, function ($app) {
            return new MACService();
        });
        
        $this->app->singleton(CredentialCheckService::class, function ($app) {
            return new CredentialCheckService(
                $app->make(PasswordService::class),
                $app->make(EncryptionService::class),
                $app->make(MACService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom middleware
        $this->app['router']->aliasMiddleware('secure.auth', \App\Http\Middleware\SecureAuth::class);
    }
}
