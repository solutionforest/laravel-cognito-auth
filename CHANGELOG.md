# Changelog

All notable changes to `laravel-cognito-auth` will be documented in this file

## 0.1.18 (Solution Forest fork)

- Support Laravel 10, 11, 12 and 13; require PHP ^8.1 (tested on PHP 8.3, 8.4 and 8.5).
- `CognitoAuthServiceProvider`: use the `$app` closure argument inside `auth()->extend()`.
  Laravel 13 binds extend callbacks to the AuthManager, so `$this->app` no longer pointed at the provider.
- New `cognito.register_routes` option (default `true`) to skip the package routes in apps that do not
  have `ResetPasswordController` / `VerificationController`.
- Test suite on Orchestra Testbench (guard resolution, attempt/SSO user creation, client calls via the
  AWS SDK `MockHandler`, routes on/off) and a GitHub Actions Laravel x PHP matrix replacing Travis/Scrutinizer.

## 0.1.3 - 2018-08-20

- implemented confirmSignUp function in CognitoClient
- added CognitoUserPropertyAccessor class 
- added delete_user config

## 0.1.2 - 2018-08-19

- implemented invalidatePassword function in CognitoClient

## 0.1.1 - 2018-08-09

- implemented userDelete function in CognitoClient

## 0.1.0 - 2018-07-04

- initial release
