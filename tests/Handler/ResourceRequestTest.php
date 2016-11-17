<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Tests\TestCase;

class ResourceRequestTest extends TestCase
{
    private $accessToken = 'XYZ321abc';

    protected function setUp()
    {
        parent::setUp();
        $this->storage->shouldReceive('get')->with('accessToken_' . $this->accessToken)
            ->andReturn([
                'user' => $this->user,
                'scopes' => ['basic']
            ])->byDefault();
    }

    public function testGetsDataStoredForAccessToken()
    {
        $this->storage->shouldReceive('get')->once()->with('accessToken_' . $this->accessToken)->andReturn(null);

        $this->handler->getUser($this->accessToken);
    }

    public function testReturnsNullIfAccessTokenInvalid()
    {
        $this->storage->shouldReceive('get')->once()->with('accessToken_' . $this->accessToken)->andReturn(null);

        $result = $this->handler->getUser($this->accessToken);

        self::assertSame(null, $result);
    }

    public function testReturnsNullIfScopeIsNotAuthorized()
    {
        $result = $this->handler->getUser($this->accessToken, 'special-scope');

        self::assertSame(null, $result);
    }

    public function testReturnsUser()
    {
        $result = $this->handler->getUser($this->accessToken);

        self::assertSame($this->user, $result);
    }
}
