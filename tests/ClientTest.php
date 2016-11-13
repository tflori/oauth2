<?php


namespace Oauth2\Tests;

use Oauth2\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testUsesRegularExpressions()
    {
        $client = new Client('~^https://(www\.)?example\.com~');

        self::assertTrue($client->isValidRedirectUri('https://www.example.com'));
        self::assertFalse($client->isValidRedirectUri('http://example.net/hacked'));
    }
}
