# tflori/oauth2

[![Build Status](https://travis-ci.org/tflori/oauth2.svg?branch=master)](https://travis-ci.org/tflori/oauth2)
[![Coverage Status](https://coveralls.io/repos/github/tflori/oauth2/badge.svg?branch=master)](https://coveralls.io/github/tflori/oauth2?branch=master)

This library helps you to create an oauth service with oauth2 three legged authentication mechanism.

Currently the only supported flow is "implicit" with refresh token:

```
     +----------+
     | Resource |
     |   Owner  |
     |          |
     +----------+
          ^
          |
         (B)
     +----|-----+          Client Identifier      +---------------+
     |         -+----(A)-- & Redirection URI ---->|               |
     |  User-   |                                 | Authorization |
     |  Agent  -+----(B)-- User authenticates --->|     Server    |
     |          |                                 |               |
     |         -+----(C)-- Authorization Code ---<|               |
     +-|----|---+                                 +---------------+
       |    |                                         ^      v
      (A)  (C)                                        |      |
       |    |                                         |      |
       ^    v                                         |      |
     +---------+                                      |      |
     |         |>---(D)-- Authorization Code ---------'      |
     |  Client |          & Redirection URI                  |
     |         |                                             |
     |         |<---(E)----- Access Token -------------------'
     +---------+       (w/ Optional Refresh Token)
```

This is described here: https://tools.ietf.org/html/rfc6749#section-4.1

## Security

How secure is oauth2 and why? The main problem is storing the secret of the client. How ever you implement it to an app
running on users end: your can reverse engineer the app to get the secret. The only way of making it secure is to store
the secret on a service that gets the authorization code and asks the authorization service for the access code.
 
Is it save then? No. Every app can ask your service. You know a way how to accomplish that the request is really from
your app? Great: send suggestions to thflori@gmail.com.

The main idea behind oauth is not to ensure the app is really the app it tells to be. The only thing we can proof is: 
the user is really the user that has the password and user identification (or how ever the authentication works in
your implementation).

## Usage

### Setup

... todo: write how to setup `composer require tflori/oauth2` ...

In the examples we will use nikic/fast-route for routing and tflori/dependency-injector for dependency injection but
you can use any other router and dependency injector.


### Obtain an authorization code (A over B to C)

The authorization code enables the client to get an access token (and refresh token). The client sends the user
to the authorization server. If the user is logged in already (usually by cookies) he sends the user back to the
callback providing the authorization code.

Example:
```php
<?php

$handler = new Oauth2\Handler(new Oauth2\Tests\Fake\Storage());
$client = new Oauth2\Client(1, '~^https://www\.example\.com~', 'my-long-secret');
$user = new Oauth2\User();
$user->permit($client, ['basic']);

$result = $handler->getAuthToken($_SESSION['oauthId'], $client, $user, $_GET['redirect_uri']);
if ($result['status'] == OAuth2\Handler::STATUS_GRANTED) {
    header('Location: ' . $result['redirectUri']);
}
```

### Obtain an access token (D to E)

With access token the client can access the data. To get an access token the client needs to provide the client id,
the client secret and the previously generated authorization code. 

Example:
```php
<?php

$handler = new Oauth2\Handler(new Oauth2\Tests\Fake\Storage());

$result = $handler->getAccessToken($_GET['client_id'], $_GET['client_secret'], $_GET['code']);
header('Content-Type: application/json');
echo json_encode($result);
```

### Check authorization

When the client request a resource it sends the access token in header 
(usually: `Authorization: Bearer <access_token>`). The resource server has to check if this access token is valid.

There are two possible scenarios:

#### Resource Server on the same server

When the resource server runs on the same server you can just create a Handler and aks him:
```php
<?php

$handler = new Oauth2\Handler(new Oauth2\Tests\Fake\Storage());

$accessToken = substr($_SERVER['HTTP_AUTHORIZATION'], strrpos($_SERVER['HTTP_AUTHORIZATION'], ' '));
if ($handler->isAuthorized($accessToken, 'this-resource')) {
    $user = $handler->getUser($accessToken);
    // request the resource and send him data
} else {
    header('HTTP/1.1 403 Forbidden');
}
```

#### Resource Server on another server

In this case you need to send a request to the authorization server:
```php
<?php

$accessToken = substr($_SERVER['HTTP_AUTHORIZATION'], strrpos($_SERVER['HTTP_AUTHORIZATION'], ' '));
$result = json_decode(file_get_contents(
    'https://auth.example.com/validate.php?access_token=' . $accessToken . '&scope=this-resource'
));
```

On the Authorisation Server run the same as before:
```php
<?php

$handler = new Oauth2\Handler(new Oauth2\Tests\Fake\Storage());

header('Content-Type: application/json');
if (!$handler->isAuthorized($_GET['access_token'], $_GET['scope'])) {
    echo json_encode(false); // or echo 'false';
} else {
    echo json_encode($handler->getUser($_GET['access_token']));
}
```

### Logout

When the user logs out every access token for the session should get invalid immediately. Nothing is easier:
```php
<?php

$handler = new Oauth2\Handler(new Oauth2\Tests\Fake\Storage());
$handler->destroySession($_SESSION['oauthId']);
```
