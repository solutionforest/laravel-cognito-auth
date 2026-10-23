<?php

namespace BlackBits\LaravelCognitoAuth\Tests;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\Command;
use Aws\Result;
use Illuminate\Support\Facades\Password;

class CognitoClientTest extends TestCase
{
    public function testAuthenticateSendsSecretHashAndReturnsResult(): void
    {
        $this->aws->append(new Result(['AuthenticationResult' => ['AccessToken' => 'token']]));

        $result = $this->client($this->aws)->authenticate('user@example.com', 'secret');

        $this->assertSame('token', $result['AuthenticationResult']['AccessToken']);
        $command = $this->aws->getLastCommand();
        $this->assertSame('AdminInitiateAuth', $command->getName());
        $this->assertSame('ADMIN_NO_SRP_AUTH', $command['AuthFlow']);
        $this->assertSame($this->secretHash('user@example.com'), $command['AuthParameters']['SECRET_HASH']);
        $this->assertSame('eu-west-1_TestPool', $command['UserPoolId']);
    }

    public function testAuthenticateReturnsFalseForUnknownUser(): void
    {
        $this->aws->append($this->awsError('UserNotFoundException'));

        $this->assertFalse($this->client($this->aws)->authenticate('missing@example.com', 'secret'));
    }

    public function testAuthenticateRethrowsOtherAwsErrors(): void
    {
        $this->aws->append($this->awsError('NotAuthorizedException'));

        $this->expectException(CognitoIdentityProviderException::class);

        $this->client($this->aws)->authenticate('user@example.com', 'wrong');
    }

    public function testSendResetLink(): void
    {
        $this->aws->append(new Result([]));

        $this->assertSame(Password::RESET_LINK_SENT, $this->client($this->aws)->sendResetLink('user@example.com'));
        $this->assertSame('ForgotPassword', $this->aws->getLastCommand()->getName());
    }

    public function testSendResetLinkForUnknownUser(): void
    {
        $this->aws->append($this->awsError('UserNotFoundException'));

        $this->assertSame(Password::INVALID_USER, $this->client($this->aws)->sendResetLink('missing@example.com'));
    }

    public function testResetPassword(): void
    {
        $this->aws->append(new Result([]));

        $this->assertSame(Password::PASSWORD_RESET, $this->client($this->aws)->resetPassword('123456', 'user@example.com', 'NewPassword1!'));
        $command = $this->aws->getLastCommand();
        $this->assertSame('ConfirmForgotPassword', $command->getName());
        $this->assertSame('123456', $command['ConfirmationCode']);
    }

    public function testResetPasswordMapsExpiredCodeToInvalidToken(): void
    {
        $this->aws->append($this->awsError('ExpiredCodeException'));

        $this->assertSame(Password::INVALID_TOKEN, $this->client($this->aws)->resetPassword('123456', 'user@example.com', 'NewPassword1!'));
    }

    public function testGetUserReturnsFalseForUnknownUser(): void
    {
        $this->aws->append($this->awsError('UserNotFoundException'));

        $this->assertFalse($this->client($this->aws)->getUser('missing@example.com'));
    }

    private function awsError(string $code): callable
    {
        return fn (Command $command) => new CognitoIdentityProviderException($code, $command, ['code' => $code]);
    }
}
