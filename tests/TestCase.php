<?php

namespace Oauth2\Tests;

use Mockery\Mock;
use Oauth2\Handler;
use Oauth2\Client;
use Oauth2\Tests\Fake\Storage;
use Oauth2\User;
use SecureToken\Token;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Handler|mock */
    protected $handler;

    /** @var User|Mock */
    protected $user;

    /** @var Client|Mock */
    protected $client;

    /** @var Token|Mock */
    protected $token;

    /** @var Storage|Mock */
    protected $storage;

    protected function setUp()
    {
        parent::setUp();

        // create mocks
        $this->user    = \Mockery::mock(User::class)->makePartial();
        $this->token   = \Mockery::mock(Token::class)->makePartial();
        $this->storage = \Mockery::mock(Storage::class)->makePartial();
        $this->client  = \Mockery::mock(Client::class, [
            'example',
            '~^https://(www\.)?example\.com~',
            'fooBar'
        ])->makePartial();
        $this->handler = \Mockery::mock(Handler::class, [
            $this->storage,
            [
                Handler::OPTION_TOKEN_CLASS => $this->token
            ]
        ])->makePartial();

        // prepare defaults
        $this->user->shouldReceive('hasPermitted')->andReturn(true)->byDefault();
        $this->token->shouldReceive('generate')->andReturn('abc123XYZ')->byDefault();
    }

    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }
}
