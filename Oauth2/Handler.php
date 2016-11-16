<?php

namespace Oauth2;

use Oauth2\Interfaces;

class Handler
{
    const STATUS_NEEDS_GRANT = 'needsGrant';
    const STATUS_GRANTED     = 'granted';

    const OPTION_TOKEN_CLASS            = 'tokenClass';
    const OPTION_PREFIX_AUTH_TOKEN      = 'prefixAuthToken';
    const OPTION_PREFIX_ACCESS_TOKEN    = 'prefixAccessToken';
    const OPTION_PREFIX_REFRESH_TOKEN   = 'prefixRefreshToken';
    const OPTION_PREFIX_TOKENS          = 'prefixTokens';
    const OPTION_PREFIX_SESSION_TOKENS  = 'prefixSessionTokens';
    const OPTION_LIFETIME_AUTH_TOKEN    = 'lifetimeAuthToken';
    const OPTION_LIFETIME_ACCESS_TOKEN  = 'lifetimeAccessToken';
    const OPTION_LIFETIME_REFRESH_TOKEN = 'lifetimeRefreshToken';

    /** @var Interfaces\Storage */
    protected $tokenStorage;

    /** @var array */
    protected $options = [
        self::OPTION_TOKEN_CLASS            => 'SecureToken\Token',
        self::OPTION_PREFIX_AUTH_TOKEN      => 'authToken_',
        self::OPTION_PREFIX_ACCESS_TOKEN    => 'accessToken_',
        self::OPTION_PREFIX_REFRESH_TOKEN   => 'refreshToken_',
        self::OPTION_PREFIX_TOKENS          => 'tokens_',
        self::OPTION_PREFIX_SESSION_TOKENS  => 'sessionTokens_',
        self::OPTION_LIFETIME_AUTH_TOKEN    => 10,
        self::OPTION_LIFETIME_ACCESS_TOKEN  => 300,
        self::OPTION_LIFETIME_REFRESH_TOKEN => 3600,
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
     * Handle the authorization request of a client.
     *
     * Returns an array with status and redirectUri if status is Granted.
     *
     * @param string $sessionId
     * @param Interfaces\Client $client
     * @param Interfaces\User $user
     * @param $redirectUri
     * @param array $scopes
     * @return array
     * @throws Exception
     */
    public function getAuthToken(
        $sessionId,
        Interfaces\Client $client,
        Interfaces\User $user,
        $redirectUri,
        array $scopes = ['basic']
    ) {

        if (!$client->isValidRedirectUri($redirectUri)) {
            throw new Exception('Redirect URI is invalid');
        }

        if ($user->hasPermitted($client, $scopes)) {
            /** @var Interfaces\Token $class */
            $class     = $this->options[self::OPTION_TOKEN_CLASS];
            $authToken = $class::generate();

            $this->tokenStorage->save($this->options[self::OPTION_PREFIX_AUTH_TOKEN] . $authToken, [
                'client'  => $client,
                'payload' => [
                    'user'  => $user,
                    'scopes' => $scopes
                ],
            ], $this->options[self::OPTION_LIFETIME_AUTH_TOKEN]);

            $sessionStorageKey = $this->options[self::OPTION_PREFIX_SESSION_TOKENS] . $sessionId;
            $this->tokenStorage->save(
                $sessionStorageKey,
                array_merge($this->tokenStorage->get($sessionStorageKey) ?: [], [$authToken]),
                0
            );

            if (strpos($redirectUri, '%CODE%') !== false) {
                $redirectUri = str_replace('%CODE%', $authToken, $redirectUri);
            } elseif (strpos($redirectUri, '?') !== false) {
                $redirectUri = str_replace('?', '?code=' . $authToken . '&', $redirectUri);
            } else {
                $redirectUri = $redirectUri . '?code=' . $authToken;
            }

            return [
                'status'      => self::STATUS_GRANTED,
                'redirectUri' => $redirectUri
            ];
        }

        return ['status' => self::STATUS_NEEDS_GRANT];
    }

    protected function checkClient(Interfaces\Client $client, $clientId, $clientSecret)
    {
        if ($clientId !== $client->getId()) {
            throw new Exception('Client id is invalid');
        }

        if ($clientSecret !== $client->getSecret()) {
            throw new Exception('Client secret is invalid');
        }
    }

    protected function generateAccessToken(Interfaces\Client $client, $payload, $authToken)
    {
        /** @var Interfaces\Token $class */
        $class        = $this->options[self::OPTION_TOKEN_CLASS];
        $accessToken  = $class::generate();
        $refreshToken = $class::generate();

        $this->tokenStorage->save(
            $this->options[self::OPTION_PREFIX_ACCESS_TOKEN] . $accessToken,
            $payload,
            $this->options[self::OPTION_LIFETIME_ACCESS_TOKEN]
        );

        $this->tokenStorage->save(
            $this->options[self::OPTION_PREFIX_REFRESH_TOKEN] . $refreshToken,
            [
                'client' => $client,
                'payload' => $payload,
                'authToken' => $authToken
            ],
            $this->options[self::OPTION_LIFETIME_REFRESH_TOKEN]
        );

        $this->tokenStorage->save(
            $this->options[self::OPTION_PREFIX_TOKENS] . $authToken,
            [
                'accessToken'  => $accessToken,
                'refreshToken' => $refreshToken
            ],
            0
        );

        return [
            'access_token'  => $accessToken,
            'expires_in'    => $this->options[self::OPTION_LIFETIME_ACCESS_TOKEN],
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * Check validity of $authToken and return an array that should be send back to the client.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $authToken
     * @return array
     * @throws Exception
     */
    public function getAccessToken($clientId, $clientSecret, $authToken)
    {
        $authData = $this->tokenStorage->get($this->options[self::OPTION_PREFIX_AUTH_TOKEN] . $authToken);

        if (!$authData) {
            throw new Exception('Authorization token is invalid');
        }

        /** @var Interfaces\Client $client */
        $client = $authData['client'];

        $this->checkClient($client, $clientId, $clientSecret);

        $this->tokenStorage->delete($this->options[self::OPTION_PREFIX_AUTH_TOKEN] . $authToken);

        return $this->generateAccessToken($client, $authData['payload'], $authToken);
    }

    public function refreshAccessToken($clientId, $clientSecret, $refreshToken)
    {
        $authData = $this->tokenStorage->get($this->options[self::OPTION_PREFIX_REFRESH_TOKEN] . $refreshToken);

        if (!$authData) {
            throw new Exception('Refresh token is invalid');
        }

        /** @var Interfaces\Client $client */
        $client = $authData['client'];

        $this->checkClient($client, $clientId, $clientSecret);

        $previousTokens = $this->tokenStorage->get($this->options[self::OPTION_PREFIX_TOKENS] . $authData['authToken']);
        $this->tokenStorage->delete(
            $this->options[self::OPTION_PREFIX_ACCESS_TOKEN] . $previousTokens['accessToken']
        );
        $this->tokenStorage->delete(
            $this->options[self::OPTION_PREFIX_REFRESH_TOKEN] . $previousTokens['refreshToken']
        );

        return $this->generateAccessToken($client, $authData['payload'], $authData['authToken']);
    }
}
