<?php

namespace Oauth2\Interfaces;

interface User {

    /**
     * Returns whether the user permitted the client access or not.
     *
     * @param Client $client
     * @param array $scopes
     * @return boolean
     */
    public function hasPermitted(Client $client, array $scopes);
}
