<?php

namespace BlackBits\LaravelCognitoAuth\Tests;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\MockHandler;
use BlackBits\LaravelCognitoAuth\CognitoAuthServiceProvider;
use BlackBits\LaravelCognitoAuth\CognitoClient;
use BlackBits\LaravelCognitoAuth\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected MockHandler $aws;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Route every Cognito API call of the package client through the AWS SDK mock handler.
        $this->aws = new MockHandler();
        $this->app->forgetInstance(CognitoClient::class);
        $this->app->singleton(CognitoClient::class, fn () => $this->client($this->aws));
    }

    protected function getPackageProviders($app)
    {
        return [CognitoAuthServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('k', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('auth.guards.cognito', ['driver' => 'cognito', 'provider' => 'users']);
        $app['config']->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);
        $app['config']->set('cognito.app_client_id', 'client-id');
        $app['config']->set('cognito.app_client_secret', 'client-secret');
        $app['config']->set('cognito.user_pool_id', 'eu-west-1_TestPool');
        $app['config']->set('cognito.region', 'eu-west-1');
        $app['config']->set('cognito.sso_user_model', User::class);
    }

    protected function client(MockHandler $mock): CognitoClient
    {
        return new CognitoClient(
            new CognitoIdentityProviderClient([
                'region' => 'eu-west-1',
                'version' => '2016-04-18',
                'credentials' => ['key' => 'test', 'secret' => 'test'],
                'handler' => $mock,
            ]),
            'client-id',
            'client-secret',
            'eu-west-1_TestPool'
        );
    }

    protected function secretHash(string $username): string
    {
        return base64_encode(hash_hmac('sha256', $username . 'client-id', 'client-secret', true));
    }
}
