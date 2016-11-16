<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Exception;
use Oauth2\Handler;
use Oauth2\Tests\TestCase;

class AuthorizationRequestTest extends TestCase
{

    public function testInvalidRedirectUriThrows()
    {
        $this->client->shouldReceive('isValidRedirectUri')->once()->with('https://www.example.net/hacked');

        self::expectException(Exception::class);
        self::expectExceptionMessage('Redirect URI is invalid');

        $this->handler->checkAuth(
            $this->client,
            $this->user,
            'https://www.example.net/hacked',
            ['basic']
        );
    }

    public function testNotPermittedClient()
    {
        $this->user->shouldReceive('hasPermitted')->once()->with($this->client, ['basic'])
                   ->andReturn(false);

        $valid = $this->handler->checkAuth(
            $this->client,
            $this->user,
            'https://www.example.com/callback',
            ['basic']
        );

        self::assertFalse($valid);
    }

    public function testThrowsWhenClientNotPermitted()
    {
        $valid = $this->handler->checkAuth(
            $this->client,
            $this->user,
            'https://www.example.com/callback',
            ['basic']
        );

        self::assertTrue($valid);
    }

    public function redirectUriProvider()
    {
        return [
            [
                'https://example.com/callback?auth=%CODE%',
                'https://example.com/callback',
                ['auth=exampleCode']
            ],
            [
                'https://www.example.com/cb?another=query',
                'https://www.example.com/cb',
                ['another=query', 'code=exampleCode']
            ],
            [
                'https://www.example.com/deep/inside/a/structure',
                'https://www.example.com/deep/inside/a/structure',
                ['code=exampleCode']
            ]
        ];
    }

    /**
     * @dataProvider redirectUriProvider
     * @param string $redirectUri
     * @param string $expectedUri
     * @param array $expectedQuery
     */
    public function testReturnsRedirectUri($redirectUri, $expectedUri, $expectedQuery)
    {
        $result = $this->handler->generateRedirectUri(
            $redirectUri,
            'exampleCode'
        );

        self::assertStringStartsWith($expectedUri, $result);

        $query = parse_url($result, PHP_URL_QUERY);
        self::assertEmpty(
            array_diff($expectedQuery, explode('&', $query)),
            'Failed asserting that ' . $query . ' contains '
            . implode(', ', array_diff($expectedQuery, explode('&', $query))) . '.'
        );
    }

    public function testUsesTokenClass()
    {
        $this->token->shouldReceive('generate')->once()->andReturn('fakeToken');

        $token = $this->handler->generateAuthToken($this->client, ['some' => 'data']);

        self::assertSame('fakeToken', $token);
    }

    public function testStoresAuthToken()
    {
        $this->storage->shouldReceive('save')->once()->with('authToken_abc123XYZ', [
            'client'  => $this->client,
            'payload' => ['some' => 'data'],
        ], 10);

        $token = $this->handler->generateAuthToken($this->client, ['some' => 'data']);

        self::assertSame('abc123XYZ', $token);
    }

    public function testGetAuthTokenNeedsGrant()
    {
        $redirectUri = 'https://example.com/cb';
        $this->handler->shouldReceive('checkAuth')->once()
                      ->with($this->client, $this->user, $redirectUri, ['basic'])
                      ->andReturn(false);

        $result = $this->handler->getAuthToken(
            str_repeat('f', 32),
            $this->client,
            $this->user,
            $redirectUri
        );

        self::assertSame(['status' => Handler::STATUS_NEEDS_GRANT], $result);
    }

    public function testGetAuthTokenGrantedResult()
    {
        $redirectUri       = 'https://example.com/cb';
        $redirectUriResult = 'https://example.com/cb?grant=ABC123xyz';
        $this->handler->shouldReceive('generateAuthToken')->once()
                      ->with($this->client, ['user' => $this->user, 'scopes' => ['basic']])
                      ->andReturn('ABC123xyz');
        $this->handler->shouldReceive('generateRedirectUri')->once()
                      ->with($redirectUri, 'ABC123xyz')
                      ->andReturn($redirectUriResult);

        $result = $this->handler->getAuthToken(
            str_repeat('f', 32),
            $this->client,
            $this->user,
            $redirectUri
        );

        self::assertSame([
            'status'      => Handler::STATUS_GRANTED,
            'redirectUri' => $redirectUriResult
        ], $result);
    }

    public function testGetAuthTokenGrantedStore()
    {
        $redirectUri = 'https://example.com/cb';
        $sessionId   = str_repeat('f', 32);

        $this->handler->shouldReceive('generateAuthToken')->once()
                      ->with($this->client, ['user' => $this->user, 'scopes' => ['basic']])
                      ->andReturn('ABC123xyz');
        $this->storage->shouldReceive('save')->once()
                      ->with('sessionTokens_' . $sessionId, ['abc123XYZ', 'ABC123xyz'], 0);
        $this->storage->shouldReceive('get')->once()
                      ->with('sessionTokens_' . $sessionId)
                      ->andReturn(['abc123XYZ']);

        $this->handler->getAuthToken(
            $sessionId,
            $this->client,
            $this->user,
            $redirectUri
        );
    }
}
