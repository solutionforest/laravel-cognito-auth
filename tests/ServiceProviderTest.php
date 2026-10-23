<?php

namespace BlackBits\LaravelCognitoAuth\Tests;

use BlackBits\LaravelCognitoAuth\CognitoClient;
use Illuminate\Support\Facades\Route;

class ServiceProviderTest extends TestCase
{
    public function testConfigIsMerged(): void
    {
        $this->assertTrue(config('cognito.register_routes'));
        $this->assertSame(['name', 'email'], config('cognito.sso_user_fields'));
    }

    public function testPackageRoutesAreRegisteredByDefault(): void
    {
        $this->assertTrue(Route::has('cognito.password-reset'));
        $this->assertTrue(Route::has('cognito.verification-notice'));
        $this->assertTrue(Route::has('cognito.verification-resend'));
    }

    public function testClientIsResolvableFromTheContainer(): void
    {
        $this->assertInstanceOf(CognitoClient::class, $this->app->make(CognitoClient::class));
    }

    public function testViewsAndTranslationsAreRegistered(): void
    {
        $this->assertTrue(view()->exists('black-bits/laravel-cognito-auth::reset-password'));
    }
}
