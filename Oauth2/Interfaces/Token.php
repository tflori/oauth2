<?php

namespace Oauth2\Interfaces;

interface Token
{
    /**
     * Generate a token with $bitLength in $base.
     *
     * @param int $bitLength
     * @param int $base
     * @return string
     */
    public static function generate($bitLength = 256, $base = 62);
}
