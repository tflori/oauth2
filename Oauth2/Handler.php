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
     * Check authentication of Client to obtain an auth Token.
     *
     * You should run this before generating token. It is not needed, but it holds all logic to check
     * if client is granted to get an auth token.
     *
     * @param Interfaces\Client $client
     * @param Interfaces\User $user
     * @param string $redirectUri
     * @param string[] $scopes
     * @return bool
     * @throws Exception
     */
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

    /**
     * Generate an authorization for $client and store it with $payload.
     *
     * We recommend to store Information about the user in $payload and to store the returned $authToken
     * to revoke all authorizations when user logs out.
     *
     * @param Interfaces\Client $client
     * @param mixed $payload
     * @return string
     */
    public function generateAuthToken(Interfaces\Client $client, $payload = [])
    {
        /** @var Interfaces\Token $class */
        $class     = $this->options[self::OPTION_TOKEN_CLASS];
        $authToken = $class::generate();

        $this->tokenStorage->save($this->options[self::OPTION_PREFIX_AUTH_TOKEN] . $authToken, [
            'client'  => $client,
            'payload' => $payload,
        ], $this->options[self::OPTION_LIFETIME_AUTH_TOKEN]);

        return $authToken;
    }

    /**
     * Generate the URI for callback by given $redirectUri and $authToken.
     *
     * @param string $redirectUri
     * @param string $authToken
     * @return string
     */
    public function generateRedirectUri($redirectUri, $authToken)
    {
        if (strpos($redirectUri, '%CODE%') !== false) {
            return str_replace('%CODE%', $authToken, $redirectUri);
        } elseif (strpos($redirectUri, '?') !== false) {
            return str_replace('?', '?code=' . $authToken . '&', $redirectUri);
        } else {
            return $redirectUri . '?code=' . $authToken;
        }
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
     */
    public function getAuthToken(
        $sessionId,
        Interfaces\Client $client,
        Interfaces\User $user,
        $redirectUri,
        array $scopes = ['basic']
    ) {
        if ($this->checkAuth($client, $user, $redirectUri, $scopes)) {
            $authToken = $this->generateAuthToken($client, [
                'user'  => $user,
                'scope' => $scopes
            ]);

            $sessionStorageKey = $this->options[self::OPTION_PREFIX_SESSION_TOKENS] . $sessionId;
            $this->tokenStorage->save(
                $sessionStorageKey,
                array_merge($this->tokenStorage->get($sessionStorageKey) ?: [], [$authToken]),
                0
            );

            return [
                'status'      => self::STATUS_GRANTED,
                'redirectUri' => $this->generateRedirectUri($redirectUri, $authToken)
            ];
        }

        return ['status' => self::STATUS_NEEDS_GRANT];
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

        if ($clientId !== $client->getId()) {
            throw new Exception('Client id is invalid');
        }

        if ($clientSecret !== $client->getSecret()) {
            throw new Exception('Client secret is invalid');
        }

        /** @var Interfaces\Token $class */
        $class        = $this->options[self::OPTION_TOKEN_CLASS];
        $accessToken  = $class::generate();
        $refreshToken = $class::generate();

        $this->tokenStorage->save(
            $this->options[self::OPTION_PREFIX_ACCESS_TOKEN] . $accessToken,
            $authData['payload'],
            $this->options[self::OPTION_LIFETIME_ACCESS_TOKEN]
        );

        $this->tokenStorage->save(
            $this->options[self::OPTION_PREFIX_REFRESH_TOKEN] . $refreshToken,
            $authData,
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

        $this->tokenStorage->delete($this->options[self::OPTION_PREFIX_AUTH_TOKEN] . $authToken);

        return [
            'access_token'  => $accessToken,
            'expires_in'    => $this->options[self::OPTION_LIFETIME_ACCESS_TOKEN],
            'refresh_token' => $refreshToken
        ];
    }
}
