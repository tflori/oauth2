<?php

namespace Oauth2\Tests\Handler;

use Oauth2\Exception;
use Oauth2\Tests\TestCase;

class AuthorizationRequestTest extends TestCase
{

    public function testInvalidRedirectUriThrows() {
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

    public function testNotPermittedClient() {
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

    public function testThrowsWhenClientNotPermitted() {
        $valid = $this->handler->checkAuth(
            $this->client,
            $this->user,
            'https://www.example.com/callback',
            ['basic']
        );

        self::assertTrue($valid);
    }

    public function redirectUriProvider() {
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
    public function testReturnsRedirectUri($redirectUri, $expectedUri, $expectedQuery) {
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

}
