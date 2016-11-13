<?php

namespace Oauth2;

class Client implements Interfaces\Client
{
    /** @var string */
    protected $redirectUriPattern;

    /**
     * Client constructor.
     *
     * @param string $redirectUriPattern A regular expression the redirect uri has to match against.
     */
    public function __construct($redirectUriPattern)
    {
        $this->redirectUriPattern = $redirectUriPattern;
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
}
