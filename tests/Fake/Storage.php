<?php

namespace Oauth2\Tests\Fake;

class Storage implements \Oauth2\Interfaces\Storage
{
    public function get($key)
    {
        return null;
    }

    public function save($key, $value, $ttl = null)
    {
    }
}
