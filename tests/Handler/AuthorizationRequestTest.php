<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Exception;
use Oauth2\Handler;
use Oauth2\Tests\TestCase;

class AuthorizationRequestTest extends TestCase
{
    private $sessionId = 'ffffff';
    private $redirectUri = 'https://www.example.com/callback';
    private $redirectUriResult = 'https://www.example.com/callback?code=ABC123xyz';

    protected function setUp()
    {
        parent::setUp();

        $this->token->shouldReceive('generate')->andReturn('ABC123xyz')->byDefault();
    }


    public function testInvalidRedirectUriThrows()
    {
        $this->client->shouldReceive('isValidRedirectUri')->once()->with('https://www.example.net/hacked');

        self::expectException(Exception::class);
        self::expectExceptionMessage('Redirect URI is invalid');

        $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            'https://www.example.net/hacked'
        );
    }

    public function testUsesTokenClass()
    {
        $this->token->shouldReceive('generate')->once()->andReturn('fakeToken');

        $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $this->redirectUri
        );
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
            ],
        ];
    }

    /**
     * @dataProvider redirectUriProvider
     * @param string $redirectUri
     * @param string $expectedUri
     * @param array $expectedQuery
     * @depends testUsesTokenClass
     */
    public function testAppendsCodeToRedirectUri($redirectUri, $expectedUri, $expectedQuery)
    {
        $this->token->shouldReceive('generate')->once()->andReturn('exampleCode');

        $result = $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $redirectUri
        );

        self::assertStringStartsWith($expectedUri, $result['redirectUri']);

        $query = parse_url($result['redirectUri'], PHP_URL_QUERY);
        self::assertEmpty(
            array_diff($expectedQuery, explode('&', $query)),
            'Failed asserting that ' . $query . ' contains '
            . implode(', ', array_diff($expectedQuery, explode('&', $query))) . '.'
        );
    }

    public function testStoresAuthToken()
    {
        $this->storage->shouldReceive('set')->once()->with('authToken_ABC123xyz', [
            'client'  => $this->client,
            'payload' => ['user' => $this->user, 'scopes' => ['basic', 'write-messages']],
        ], 10);

        $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $this->redirectUri,
            ['basic', 'write-messages']
        );
    }

    public function testGetAuthTokenNeedsGrant()
    {
        $this->user->shouldReceive('hasPermitted')->once()->with($this->client, ['basic', 'read-messages'])
                   ->andReturn(false);

        $result = $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $this->redirectUri,
            ['basic', 'read-messages']
        );

        self::assertSame(['status' => Handler::STATUS_NEEDS_GRANT], $result);
    }

    public function testGetAuthTokenGrantedResult()
    {
        $result = $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $this->redirectUri
        );

        self::assertSame([
            'status'      => Handler::STATUS_GRANTED,
            'redirectUri' => $this->redirectUriResult
        ], $result);
    }

    public function testGetAuthTokenGrantedStore()
    {
        $this->storage->shouldReceive('set')->once()
                      ->with('sessionTokens_' . $this->sessionId, ['abc123XYZ', 'ABC123xyz'], 0);
        $this->storage->shouldReceive('get')->once()
                      ->with('sessionTokens_' . $this->sessionId)
                      ->andReturn(['abc123XYZ']);

        $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $this->redirectUri
        );
    }

    public function redirectUriStateProvider()
    {
        return [
            ['https://example.com/cb/%CODE%/%STATE%', 'https://example.com/cb/ABC123xyz/abcdef'],
            ['https://example.com/cb/%CODE%', 'https://example.com/cb/ABC123xyz', ['state=abcdef']],
            ['https://example.com/cb?a=b', 'https://example.com/cb', ['code=ABC123xyz','state=abcdef','a=b']]
        ];
    }

    /**
     * @dataProvider redirectUriStateProvider
     * @param $redirectUri
     * @param $expectedUri
     * @param array $expectedQuery
     */
    public function testAppendsStateIfGiven($redirectUri, $expectedUri, $expectedQuery = [])
    {
        $state = 'abcdef';
        $result = $this->handler->getAuthToken(
            $this->sessionId,
            $this->client,
            $this->user,
            $redirectUri,
            ['basic'],
            $state
        );

        self::assertStringStartsWith($expectedUri, $result['redirectUri']);

        $query = parse_url($result['redirectUri'], PHP_URL_QUERY);
        self::assertEmpty(
            array_diff($expectedQuery, explode('&', $query)),
            'Failed asserting that ' . $query . ' contains '
            . implode(', ', array_diff($expectedQuery, explode('&', $query))) . '.'
        );
    }
}
