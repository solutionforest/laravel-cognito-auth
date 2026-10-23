<?php

namespace BlackBits\LaravelCognitoAuth;

use Illuminate\Support\Arr;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use BlackBits\LaravelCognitoAuth\Auth\CognitoGuard;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

class CognitoAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/cognito.php' => config_path('cognito.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/Resources/views' => resource_path('views/vendor/black-bits/laravel-cognito-auth'),
        ], 'views');

        $this->publishes([
            __DIR__.'/Resources/lang' => resource_path('lang/vendor/black-bits/laravel-cognito-auth'),
        ], 'lang');

        $this->app->singleton(CognitoClient::class, function (Application $app) {
            $config = [
                'region'      => config('cognito.region'),
                'version'     => config('cognito.version'),
            ];

            $credentials = config('cognito.credentials');

            if (! empty($credentials['key']) && ! empty($credentials['secret'])) {
                $config['credentials'] = Arr::only($credentials, ['key', 'secret', 'token']);
            }

            return new CognitoClient(
                new CognitoIdentityProviderClient($config),
                config('cognito.app_client_id'),
                config('cognito.app_client_secret'),
                config('cognito.user_pool_id')
            );
        });

        $this->app['auth']->extend('cognito', function (Application $app, $name, array $config) {
            $guard = new CognitoGuard(
                $name,
                $client = $app->make(CognitoClient::class),
                $app['auth']->createUserProvider($config['provider']),
                $app['session.store'],
                $app['request']
            );

            // Laravel 13 binds extend() callbacks to the AuthManager, so use $app instead of $this->app.
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });

        // The routes point at app controllers (ResetPasswordController, VerificationController);
        // apps without them can turn the routes off with cognito.register_routes = false.
        if (config('cognito.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/routes.php');
        }
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'black-bits/laravel-cognito-auth');
        $this->loadTranslationsFrom(__DIR__.'/Resources/lang', 'black-bits/laravel-cognito-auth');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cognito.php', 'cognito');
    }
}
