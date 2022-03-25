# Sign in with Apple ID Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/patrickbussmann/oauth2-apple.svg?style=flat-square)](https://github.com/patrickbussmann/oauth2-apple/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/patrickbussmann/oauth2-apple/main.svg?style=flat-square)](https://travis-ci.org/patrickbussmann/oauth2-apple)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/patrickbussmann/oauth2-apple.svg?style=flat-square)](https://scrutinizer-ci.com/g/patrickbussmann/oauth2-apple/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/patrickbussmann/oauth2-apple.svg?style=flat-square)](https://scrutinizer-ci.com/g/patrickbussmann/oauth2-apple)
[![codecov](https://codecov.io/gh/patrickbussmann/oauth2-apple/branch/main/graph/badge.svg?token=TN3ZNVHUXV)](https://codecov.io/gh/patrickbussmann/oauth2-apple)
[![Total Downloads](https://img.shields.io/packagist/dt/patrickbussmann/oauth2-apple.svg?style=flat-square)](https://packagist.org/packages/patrickbussmann/oauth2-apple)

This package provides Apple ID OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Before You Begin

Here you can find the official Apple documentation:
https://developer.apple.com/documentation/signinwithapplerestapi

If you request email address or name please note that you'll get this only in your first login.
When you log in a second time you will only get the user id - nothing more.
Maybe Apple changes this sometime.

## Installation

To install, use composer:

```
composer require patrickbussmann/oauth2-apple
```

## Usage

Usage is the same as The League's OAuth client, using `\League\OAuth2\Client\Provider\Apple` as the provider.

### Authorization Code Flow

```php
// $leeway is needed for clock skew
Firebase\JWT\JWT::$leeway = 60;

$provider = new League\OAuth2\Client\Provider\Apple([
    'clientId'          => '{apple-client-id}',
    'teamId'            => '{apple-team-id}', // 1A234BFK46 https://developer.apple.com/account/#/membership/ (Team ID)
    'keyFileId'         => '{apple-key-file-id}', // 1ABC6523AA https://developer.apple.com/account/resources/authkeys/list (Key ID)
    'keyFilePath'       => '{apple-key-file-path}', // __DIR__ . '/AuthKey_1ABC6523AA.p8' -> Download key above
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_POST['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_POST['state']) || ($_POST['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    /** @var AppleAccessToken $token */
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_POST['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    // Important: The most details are only visible in the very first login!
    // In the second and third and ... ones you'll only get the identifier of the user!
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getFirstName());

    } catch (Exception $e) {

        // Failed to get user details
        exit(':-(');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Apple authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    // Scopes: https://developer.apple.com/documentation/authenticationservices/asauthorizationscope
    'scope' => ['name', 'email'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the following scopes are available.

- name (default)
- email (default)

Please note that you will get this informations only at the first log in of the user!
In the following log ins you'll get only the user id!

If you only want to get the user id, you can set the `scope` as ` `, then change all the `$_POST` to `$_GET`.

### Refresh Tokens

If your access token expires you can refresh them with the refresh token.

```
$refreshToken = $token->getRefreshToken();
$refreshTokenExpiration = $token->getRefreshTokenExpires();
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/patrickbussmann/oauth2-apple/blob/main/CONTRIBUTING.md) for details.


## Credits

- [All Contributors](https://github.com/patrickbussmann/oauth2-apple/contributors)

Template for this repository was the [LinkedIn](https://github.com/thephpleague/oauth2-linkedin).

## License

The MIT License (MIT). Please see [License File](https://github.com/patrickbussmann/oauth2-apple/blob/main/LICENSE) for more information.
