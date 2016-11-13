# tflori/oauth2

This library helps you to create a oauth service with oauth2 three legged authentication mechanism.

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

## Usage

### Setup

... todo: write how to setup `composer require tflori/oauth2` ...

In the examples we will use nikic/fast-route for routing and tflori/dependency-injector for dependency injection but
you can use any other router and dependency injector.


### (A) Obtain an authorization code

The authorization code gives enables the client to get an access token (and refresh token).

Example:
```php
<?php

// prepare minimal object set
$handler = new Oauth2\Handler(new Oauth2\Tests\Fake\Storage());
$client = new \Oauth2\Client('~^https://www\.example\.com~');
$user = new \Oauth2\User();
$user->permit($client, ['basic']);

// check if the user granted access and redirect_uri is valid
if ($handler->checkAuth($client, $user, $_GET['redirect_uri'])) {
    // get the authCode
    $authCode = $handler->generateAuthToken($client, ['accountId' => 123, 'email' => 'john.doe@example.com']);
    
    // send the browser to the callback
    header('Location: ' . $handler->generateRedirectUri($_GET['redirect_uri'], $authCode));
}
```

#### TODO move this to cookbook 

Example controller for auth:
```php
<?php

DI::set('oauth2Handler', function() {
    return new Oauth2\Handler();
});

$defineRoutes = function (FastRoute\RouteCollector $collector) {

    $collector->addRoute('GET', '/auth/grant', function() {
    
        // do whatever you need to login and obtaing a already logged in user (session?)
        
        // here is a static user example (not recommended)
        $user = new Oauth2\User();
        
        // you can obtain your client anywhere. Here is a doctrine example:
        // $client = DI::get('entityManager')->find('Client', $_GET['client_id']);
        
        // here we using a static client (it is the only available client)
        $client = new \Oauth2\Client('~^http://www\.example\.com');
        
        /** @var \Oauth2\Handler $oauth2Handler */
        $oauth2Handler = DI::get('oauth2Handler');
        
        $redirectUri = $_GET['redirect_uri'];
        $scopes = ['basic'];
        if (!empty($_GET['scope'])) {
            $scopes = array_merge($scopes, explode(',', $_GET['scope']));
        }
        
        try {
        
            if (!$oauth2Handler->checkAuth($client, $user, $redirectUri, $scopes)) {
                // this means that the user has not granted access to the client (should he? how? - up to you)
                return; 
            }
            
            // the client is authenticated to get an auth token
            
            // obtain and store auth code
            $authCode = $oauth2Handler->generateAuthToken($client, $user);
            
            // when the user logout you may want to remove all tokens for this auth code - so store it
            // $session->set('authCodes', array_merge($session->get('authCodes', []), [$authCode])); 
            
            // send the browser to the callback
            header('Location: ' . $oauth2Handler->generateRedirectUri($redirectUri, $authCode));
            
        } catch(Oauth2\Exception $e) {
        
            // the redirect uri is invalid maybe someone wants to hack? or DOS?
            die($e->getMessage()); // Redirect URI is invalid
            
        }
        
    });
    
};
```
