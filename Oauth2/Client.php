<?php

namespace Oauth2;

class Client implements Interfaces\Client
{
    /** @var string */
    protected $redirectUriPattern;

    /** @var string */
    protected $secret;

    /** @var string|int */
    protected $id;

    /**
     * Client constructor.
     *
     * @param string|int $id
     * @param string     $redirectUriPattern A regular expression the redirect uri has to match against.
     * @param string     $secret
     */
    public function __construct($id, $redirectUriPattern, $secret)
    {
        $this->id = $id;
        $this->redirectUriPattern = $redirectUriPattern;
        $this->secret = $secret;
    }

    /**
     * Checks whether the $redirectUri is valid for this Client.
     *
     * @param string $redirectUri
     * @return bool
     */
    public function isValidRedirectUri($redirectUri)
    {
        return !!preg_match($this->redirectUriPattern, $redirectUri);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSecret()
    {
        return $this->secret;
    }
}
