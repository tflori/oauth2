<?php

namespace Oauth2;

use Oauth2\Interfaces;

class Handler
{
    const OPTION_TOKEN_CLASS = 'tokenClass';
    const OPTION_PREFIX_AUTH_TOKEN = 'prefixAuthToken';

    /** @var Interfaces\Storage */
    protected $tokenStorage;

    /** @var array */
    protected $options = [
        self::OPTION_TOKEN_CLASS => 'SecureToken\Token',
        self::OPTION_PREFIX_AUTH_TOKEN => 'authToken_'
    ];

    /**
     * Handler constructor.
     *
     * @param Interfaces\Storage $tokenStorage
     * @param array $options
     */
    public function __construct(Interfaces\Storage $tokenStorage, array $options = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->options      = array_merge($this->options, $options ?: []);
    }


    /**
     * Check authentication of Client to obtain an auth Token.
     *
     * You should run this before generating token. It is not needed, but it holds all logic to check
     * if client is granted to get an auth token.
     *
     * @param Interfaces\Client $client
     * @param Interfaces\User   $user
     * @param string            $redirectUri
     * @param string[]          $scopes
     * @return bool
     * @throws Exception
     */
    public function checkAuth(Interfaces\Client $client, Interfaces\User $user, $redirectUri, array $scopes = ['basic'])
    {
        if (!$client->isValidRedirectUri($redirectUri)) {
            throw new Exception('Redirect URI is invalid');
        }

        return $user->hasPermitted($client, $scopes);
    }

    /**
     * Generate an authorization for $client and store it with $payload.
     *
     * We recommend to store Information about the user in $payload and to store the returned $authToken
     * to revoke all authorizations when user logs out.
     *
     * @param Interfaces\Client $client
     * @param mixed             $payload
     * @return string
     */
    public function generateAuthToken(Interfaces\Client $client, $payload = [])
    {
        /** @var Interfaces\Token $class */
        $class = $this->options[self::OPTION_TOKEN_CLASS];
        $authCode = $class::generate();

        $this->tokenStorage->set($this->options[self::OPTION_PREFIX_AUTH_TOKEN] . $authCode, [
            'client' => $client,
            'payload' => $payload
        ]);

        return $authCode;
    }

    /**
     * Generate the URI for callback by given $redirectUri and $authCode.
     *
     * @param string $redirectUri
     * @param string $authCode
     * @return string
     */
    public function generateRedirectUri($redirectUri, $authCode)
    {
        if (strpos($redirectUri, '%CODE%') !== false) {
            return str_replace('%CODE%', $authCode, $redirectUri);
        } elseif (strpos($redirectUri, '?') !== false) {
            return str_replace('?', '?code=' . $authCode . '&', $redirectUri);
        } else {
            return $redirectUri . '?code=' . $authCode;
        }
    }
}
