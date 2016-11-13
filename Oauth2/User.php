<?php

namespace Oauth2;

class User implements Interfaces\User
{
    protected $grants = [];

    /**
     * @param Interfaces\Client $client
     * @param array $scopes
     */
    public function permit(Interfaces\Client $client, array $scopes)
    {
        $this->grants = array_merge($this->grants, $scopes);
    }

    /**
     * Returns whether the user permitted the client access or not.
     *
     * @param Interfaces\Client $client
     * @param array $scopes
     * @return bool
     */
    public function hasPermitted(Interfaces\Client $client, array $scopes)
    {
        return count(array_diff($scopes, $this->grants)) === 0;
    }
}
