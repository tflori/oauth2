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
     * @param mixed  $value
     * @param int    $ttl Time to live in seconds (null = default; 0 = forever)
     */
    public function set($key, $value, $ttl = null);

    /**
     * Removes $key from the storage.
     *
     * @param $key
     */
    public function delete($key);
}
