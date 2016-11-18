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
        if (!isset($this->grants[$client->getId()])) {
            $this->grants[$client->getId()] = [];
        }
        $this->grants[$client->getId()] = array_merge($this->grants[$client->getId()], $scopes);
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
        return isset($this->grants[$client->getId()]) &&
               count(array_diff($scopes, $this->grants[$client->getId()])) === 0;
    }
}
