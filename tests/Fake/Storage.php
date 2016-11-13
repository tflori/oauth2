<?php

namespace Oauth2\Tests\Fake;

class Storage implements \Oauth2\Interfaces\Storage
{
    protected $storage = [];

    public function get($key)
    {
        return @$this->storage[$key] ?: null;
    }

    public function set($key, $value)
    {
        return $this->storage[$key] = $value;
    }
}
