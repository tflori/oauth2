<?php

namespace Oauth2\Tests;


use Mockery\Mock;
use Oauth2\Handler;
use Oauth2\Interfaces;
use Oauth2\Client;
use Oauth2\User;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Handler */
    protected $handler;

    /** @var User|Mock */
    protected $user;

    /** @var Client|Mock */
    protected $client;

    protected function setUp() {
        parent::setUp();

        // create mocks
        $this->user   = \Mockery::mock(User::class)->makePartial();
        $this->client = \Mockery::mock(Client::class, ['~^https://(www\.)?example\.com~'])->makePartial();

        $this->user->permit($this->client, ['basic']);

        $this->handler = new Handler();
    }

    protected function tearDown() {
        \Mockery::close();
        parent::tearDown();
    }
}
