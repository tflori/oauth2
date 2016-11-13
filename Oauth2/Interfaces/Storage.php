<?php

namespace Oauth2\Interfaces;

interface Storage
{
    /**
     * Get stored data under $key from storage.
     *
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * Store $value in the storage under $key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value);
}
