<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Exception;
use Oauth2\Tests\TestCase;

class RefreshTokenRequestTest extends TestCase
{
    private $clientId     = 'example';
    private $clientSecret = 'fooBar';
    private $refreshToken = 'ABC123xyz';
    private $authToken    = 'abc123XYZ';
    private $payload;

    protected function setUp()
    {
        parent::setUp();

        $this->payload = ['user' => $this->user, 'scopes' => ['basic']];

        $this->storage->shouldReceive('get')
                      ->with('refreshToken_' . $this->refreshToken)
                      ->andReturn([
                          'client'    => $this->client,
                          'payload'   => $this->payload,
                          'authToken' => $this->authToken
                      ])->byDefault();

        $this->storage->shouldReceive('get')
                      ->with('tokens_' . $this->authToken)
                      ->andReturn([
                          'accessToken' => 'a',
                          'refreshToken' => 'r'
                      ])->byDefault();

        $this->token->shouldReceive('generate')->with()
                    ->andReturn('newXYZ321abc', 'newABC123xyz')->byDefault();
    }


    public function testGetsStoredInformation()
    {
        $this->storage->shouldReceive('get')->once()
                      ->with('refreshToken_' . $this->refreshToken)
                      ->andReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Refresh token is invalid');

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }

    public function testChecksClientId()
    {
        $this->client->shouldReceive('getId')->once()
                     ->andReturn('swagger');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Client id is invalid');

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }

    public function testChecksClientSecret()
    {
        $this->client->shouldReceive('getSecret')->once()
                     ->andReturn('theAnswerIs42');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Client secret is invalid');

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }

    public function testUsesTokenClass()
    {
        $this->token->shouldReceive('generate')->twice()->with()->andReturn('newXYZ321abc', 'newABC123xyz');

        $result = $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);

        self::assertSame([
            'access_token' => 'newXYZ321abc',
            'expires_in' => 300,
            'refresh_token' => 'newABC123xyz'
        ], $result);
    }

    public function testStoresPayloadForAccessToken()
    {
        $this->storage->shouldReceive('set')->once()
                      ->with('accessToken_newXYZ321abc', $this->payload, 300);

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }

    public function testStoresRefreshToken()
    {
        $this->storage->shouldReceive('set')->once()
                      ->with('refreshToken_newABC123xyz', [
                          'client' => $this->client,
                          'payload' => $this->payload,
                          'authToken' => $this->authToken
                      ], 3600);

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }

    public function testStoresTokensForAuthToken()
    {
        $this->storage->shouldReceive('set')->once()
                      ->with('tokens_' . $this->authToken, [
                          'accessToken' => 'newXYZ321abc',
                          'refreshToken' => 'newABC123xyz'
                      ], 0);

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }

    public function testRemovesTheOldTokensFromStorage()
    {
        $this->storage->shouldReceive('delete')->once()
                      ->with('refreshToken_r');
        $this->storage->shouldReceive('delete')->once()
                      ->with('accessToken_a');

        $this->handler->refreshAccessToken($this->clientId, $this->clientSecret, $this->refreshToken);
    }
}
