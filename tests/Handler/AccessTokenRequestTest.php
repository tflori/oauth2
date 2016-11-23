<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Exception;
use Oauth2\Tests\TestCase;

class AccessTokenRequestTest extends TestCase
{
    private $clientId     = 'example';
    private $clientSecret = 'fooBar';
    private $authToken    = 'abc123XYZ';
    private $payload;

    protected function setUp()
    {
        parent::setUp();

        $this->payload = ['user' => $this->user, 'scopes' => ['basic']];

        $this->storage->shouldReceive('get')
                      ->with('authToken_' . $this->authToken)
                      ->andReturn([
                          'client'  => $this->client,
                          'payload' => $this->payload
                      ])->byDefault();

        $this->token->shouldReceive('generate')->with()
                    ->andReturn('XYZ321abc', 'ABC123xyz')->byDefault();
    }


    public function testGetsStoredInformation()
    {
        $this->storage->shouldReceive('get')->once()
                      ->with('authToken_' . $this->authToken)
                      ->andReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Authorization token is invalid');

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }

    public function testChecksClientId()
    {
        $this->client->shouldReceive('getId')->once()
                     ->andReturn('swagger');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Client id is invalid');

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }

    public function testChecksClientSecret()
    {
        $this->client->shouldReceive('getSecret')->once()
                     ->andReturn('theAnswerIs42');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Client secret is invalid');

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }

    public function testUsesTokenClass()
    {
        $this->token->shouldReceive('generate')->twice()->with()->andReturn('XYZ321abc', 'ABC123xyz');

        $result = $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);

        self::assertSame([
            'access_token' => 'XYZ321abc',
            'expires_in' => 300,
            'refresh_token' => 'ABC123xyz'
        ], $result);
    }

    public function testStoresPayloadForAccessToken()
    {
        $this->storage->shouldReceive('set')->once()
            ->with('accessToken_XYZ321abc', $this->payload, 300);

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }

    public function testStoresRefreshToken()
    {
        $this->storage->shouldReceive('set')->once()
                      ->with('refreshToken_ABC123xyz', [
                          'client' => $this->client,
                          'payload' => $this->payload,
                          'authToken' => $this->authToken
                      ], 3600);

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }

    public function testStoresTokensForAuthToken()
    {
        $this->storage->shouldReceive('set')->once()
                      ->with('tokens_' . $this->authToken, [
                          'accessToken' => 'XYZ321abc',
                          'refreshToken' => 'ABC123xyz'
                      ], 0);

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }

    public function testRemovesTheAuthCodeFromStorage()
    {
        $this->storage->shouldReceive('delete')->once()
                      ->with('authToken_' . $this->authToken);

        $this->handler->getAccessToken($this->clientId, $this->clientSecret, $this->authToken);
    }
}
