<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Tests\TestCase;

class LogoutRequestTest extends TestCase
{
    private $sessionId = 'ffffff';

    protected function setUp()
    {
        parent::setUp();

        $this->storage->shouldReceive('get')
                      ->with('sessionTokens_' . $this->sessionId)
                      ->andReturn(['abc123XYZ', 'ABC123xyz'])->byDefault();
        $this->storage->shouldReceive('get')
                      ->with('tokens_abc123XYZ')
                      ->andReturn(['accessToken' => 'aaa', 'refreshToken' => 'rrr'])->byDefault();
        $this->storage->shouldReceive('get')
                      ->with('tokens_ABC123xyz')
                      ->andReturn(['accessToken' => 'bbb', 'refreshToken' => 'sss'])->byDefault();
    }

    public function testGetsTokensForSession()
    {
        $this->storage->shouldReceive('get')->once()
                      ->with('sessionTokens_' . $this->sessionId)
                      ->andReturn(null);

        $this->handler->destroySession($this->sessionId);
    }

    public function testGetsTokensForEveryAuth()
    {
        $this->storage->shouldReceive('get')
                      ->with('sessionTokens_' . $this->sessionId)
                      ->andReturn(['abc123XYZ', 'ABC123xyz']);
        $this->storage->shouldReceive('get')->once()
                      ->with('tokens_abc123XYZ')
                      ->andReturn(null);
        $this->storage->shouldReceive('get')->once()
                      ->with('tokens_ABC123xyz')
                      ->andReturn(null);

        $this->handler->destroySession($this->sessionId);
    }

    public function testDeletesAccessToken()
    {
        $this->storage->shouldReceive('delete')->once()
                      ->with('accessToken_aaa');
        $this->storage->shouldReceive('delete')->once()
                      ->with('accessToken_bbb');

        $this->handler->destroySession($this->sessionId);
    }

    public function testDeletesRefreshToken()
    {
        $this->storage->shouldReceive('delete')->once()
                      ->with('refreshToken_rrr');
        $this->storage->shouldReceive('delete')->once()
                      ->with('refreshToken_sss');

        $this->handler->destroySession($this->sessionId);
    }

    public function testDeletesTokens()
    {
        $this->storage->shouldReceive('delete')->once()
                      ->with('tokens_abc123XYZ');
        $this->storage->shouldReceive('delete')->once()
                      ->with('tokens_ABC123xyz');

        $this->handler->destroySession($this->sessionId);
    }

    public function testDeletesSessionTokens()
    {
        $this->storage->shouldReceive('delete')->once()
                      ->with('sessionTokens_' . $this->sessionId);

        $this->handler->destroySession($this->sessionId);
    }
}
