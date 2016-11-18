<?php


namespace Oauth2\Tests;

use Oauth2\Client;
use Oauth2\User;
use PHPUnit\Framework;

class UserTest extends Framework\TestCase
{
    private $client;

    public function testStoresPermit()
    {
        $user = new User();
        $user->permit($this->client, ['basic']);

        $result = $user->hasPermitted($this->client, ['basic']);

        self::assertTrue($result);
    }

    public function testStoresPermitByClient()
    {
        $user = new User();
        $user->permit(new Client(2, 'a', 'b'), ['basic']);

        $result = $user->hasPermitted($this->client, ['basic']);

        self::assertFalse($result);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->client = \Mockery::mock(Client::class, [1, '~example~', 'fooBar'])->makePartial();
    }
}
