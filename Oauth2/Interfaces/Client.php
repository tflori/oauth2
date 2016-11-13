<?php

namespace Oauth2\Interfaces;

interface Client
{
    /**
     * Checks whether the $redirectUri is valid for this Client.
     *
     * @param string $redirectUri
     * @return bool
     */
    public function isValidRedirectUri($redirectUri);
}
