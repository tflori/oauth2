<?php

namespace Oauth2;

use Oauth2\Interfaces;

class Handler
{

//    /** @var  ClientStorage */
//    protected $tokenStorage;

//    /**
//     * Handler constructor.
//     *
//     * @param ClientStorage $clientStorage
//     * @throws Exception
//     */
//    public function __construct() {
//        if (!DI::has('session')) {
//            throw new Exception('No session prepared in dependency injector');
//        }
//
//        if (!DI::has('request')) {
//            throw new Exception('No request prepared in dependency injector');
//        }
//
//        $this->clientStorage = $clientStorage;
//    }

    public function checkAuth(
        Interfaces\Client $client,
        Interfaces\User $user,
        $redirectUri,
        array $scopes = ['basic']
    ) {
        if (!$client->isValidRedirectUri($redirectUri)) {
            throw new Exception('Redirect URI is invalid');
        }

        return $user->hasPermitted($client, $scopes);
    }

    public function getAuthToken(Interfaces\Client $client, $payload) {
        // TODO generate a code
        $authCode = 'exampleCode';

        // store client and user for token

        return $authCode;
    }

    public function generateRedirectUri($redirectUri, $authCode) {
        if (strpos($redirectUri, '%CODE%') !== false) {
            return str_replace('%CODE%', $authCode, $redirectUri);
        } elseif (strpos($redirectUri, '?') !== false) {
            return str_replace('?', '?code=' . $authCode . '&', $redirectUri);
        } else {
            return $redirectUri . '?code=' . $authCode;
        }
    }
}
