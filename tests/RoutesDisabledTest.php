<?php

namespace BlackBits\LaravelCognitoAuth\Tests;

use Illuminate\Support\Facades\Route;

class RoutesDisabledTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cognito.register_routes', false);
    }

    public function testPackageRoutesCanBeTurnedOff(): void
    {
        $this->assertFalse(Route::has('cognito.password-reset'));
        $this->assertFalse(Route::has('cognito.verification-notice'));
    }
}
