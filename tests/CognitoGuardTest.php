<?php

namespace BlackBits\LaravelCognitoAuth\Tests;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\Command;
use Aws\Result;
use BlackBits\LaravelCognitoAuth\Auth\CognitoGuard;
use BlackBits\LaravelCognitoAuth\Tests\Fixtures\User;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

class CognitoGuardTest extends TestCase
{
    public function testCognitoDriverResolvesGuardWiredToTheContainer(): void
    {
        $guard = auth()->guard('cognito');

        $this->assertInstanceOf(CognitoGuard::class, $guard);
        $this->assertSame($this->app['request'], $guard->getRequest());
        $this->assertSame($this->app['events'], $guard->getDispatcher());
        $this->assertSame($this->app['cookie'], $guard->getCookieJar());
    }

    public function testAttemptLogsInExistingUserWhenCognitoAcceptsCredentials(): void
    {
        Event::fake([Attempting::class, Login::class, Failed::class]);
        $user = User::create(['name' => 'Tenant', 'email' => 'tenant@example.com']);
        $this->aws->append(new Result(['AuthenticationResult' => ['AccessToken' => 'token']]));

        $this->assertTrue(auth()->guard('cognito')->attempt(['email' => 'tenant@example.com', 'password' => 'secret']));

        $this->assertTrue($user->is(auth()->guard('cognito')->user()));
        Event::assertDispatched(Login::class);
        Event::assertNotDispatched(Failed::class);
    }

    public function testAttemptFailsWhenCognitoRejectsUnknownUser(): void
    {
        Event::fake([Attempting::class, Login::class, Failed::class]);
        User::create(['name' => 'Tenant', 'email' => 'tenant@example.com']);
        $this->aws->append(fn (Command $c) => new CognitoIdentityProviderException('x', $c, ['code' => 'UserNotFoundException']));

        $this->assertFalse(auth()->guard('cognito')->attempt(['email' => 'tenant@example.com', 'password' => 'secret']));

        $this->assertNull(auth()->guard('cognito')->user());
        Event::assertDispatched(Failed::class);
    }

    public function testAttemptFailsWithoutLocalUserWhenSsoIsOff(): void
    {
        $this->aws->append(new Result(['AuthenticationResult' => ['AccessToken' => 'token']]));

        $this->assertFalse(auth()->guard('cognito')->attempt(['email' => 'nobody@example.com', 'password' => 'secret']));
    }

    public function testAttemptCreatesLocalUserWhenSsoIsOn(): void
    {
        config(['cognito.use_sso' => true]);
        $this->aws->append(new Result(['AuthenticationResult' => ['AccessToken' => 'token']]));
        $this->aws->append(new Result([
            'Username' => 'new@example.com',
            'UserAttributes' => [
                ['Name' => 'email', 'Value' => 'new@example.com'],
                ['Name' => 'name', 'Value' => 'New User'],
                ['Name' => 'sub', 'Value' => 'ignored'],
            ],
        ]));

        $this->assertTrue(auth()->guard('cognito')->attempt(['email' => 'new@example.com', 'password' => 'secret']));

        $this->assertSame('New User', User::where('email', 'new@example.com')->value('name'));
        $this->assertSame('new@example.com', auth()->guard('cognito')->user()->email);
    }
}
