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
                'scopes' => ['basic', 'something']
            ])->byDefault();
    }

    public function testIsAuthorizedGetsDataStoredForAccessToken()
    {
        $this->storage->shouldReceive('get')->once()->with('accessToken_' . $this->accessToken)->andReturn(null);

        $result = $this->handler->isAuthorized($this->accessToken);

        self::assertFalse($result);
    }

    public function testIsAuthorizedChecksIfScopeIsValid()
    {
        self::assertTrue($this->handler->isAuthorized($this->accessToken, 'basic'));
        self::assertTrue($this->handler->isAuthorized($this->accessToken, 'something'));
        self::assertFalse($this->handler->isAuthorized($this->accessToken, 'nothing'));
    }

    public function testReturnsTheStoredUser()
    {
        $result = $this->handler->getUser($this->accessToken);

        self::assertSame($this->user, $result);
    }

    public function testReturnsNullIfAccessTokenInvalid()
    {
        $this->storage->shouldReceive('get')->twice()->with('accessToken_' . $this->accessToken)->andReturn(null);

        self::assertNull($this->handler->getUser($this->accessToken));
        self::assertNull($this->handler->getScopes($this->accessToken));
    }

    public function testReturnsTheAuthorizedScopes()
    {
        $result = $this->handler->getScopes($this->accessToken);

        self::assertSame(['basic', 'something'], $result);
    }
}
