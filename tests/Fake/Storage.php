<?php

namespace Oauth2\Tests\Fake;

class Storage implements \Oauth2\Interfaces\Storage
{
    public function get($key)
    {
        return null;
    }

    public function set($key, $value, $ttl = null)
    {
    }

    public function delete($key)
    {
    }
}
