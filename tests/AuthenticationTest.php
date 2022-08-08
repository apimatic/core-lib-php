<?php

namespace CoreLib\Tests;

use CoreLib\Authentication\Auth;
use CoreLib\Core\Request\Request;
use CoreLib\Tests\Mocking\MockHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    public function testHeaderAuth()
    {
        $request = new Request('some/path');
        $auth = Auth::or('header');
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
    }

    public function testHeaderAuthWithMissingField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required auth credentials:" .
            "\n-> Missing required header field: authorization");

        $auth = Auth::or('headerWithNull');
        MockHelper::getCoreClient()->validateAuth($auth);
    }

    public function testHeaderOrQueryAuth()
    {
        $request = new Request('some/path');
        $auth = Auth::or('header', 'query');
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path?token=someAuthToken&authorization=accessToken', $request->getQueryUrl());
    }

    public function testHeaderWithMissingFieldOrQueryAuth()
    {
        $request = new Request('some/path');
        $auth = Auth::or('headerWithNull', 'query');
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([], $request->getHeaders());
        $this->assertEquals('some/path?token=someAuthToken&authorization=accessToken', $request->getQueryUrl());
    }

    public function testHeaderOrQueryAuthWithMissingFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required auth credentials:" .
            "\n-> Missing required header field: authorization" .
            "\n-> Missing required query field: token");

        $auth = Auth::or('headerWithNull', 'queryWithNull');
        MockHelper::getCoreClient()->validateAuth($auth);
    }

    public function testHeaderAndQueryAuth()
    {
        $request = new Request('some/path');
        $auth = Auth::and('header', 'query');
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path?token=someAuthToken&authorization=accessToken', $request->getQueryUrl());
    }

    public function testHeaderWithMissingFieldAndQueryAuth()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required header field: authorization");

        $auth = Auth::and('headerWithNull', 'query');
        MockHelper::getCoreClient()->validateAuth($auth);
    }

    public function testHeaderAndQueryAuthWithMissingFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required header field: authorization");

        $auth = Auth::and('headerWithNull', 'queryWithNull');
        MockHelper::getCoreClient()->validateAuth($auth);
    }

    public function testFormOrHeaderAndQueryAuthWithMissingFields()
    {
        $request = new Request('some/path');
        $auth = Auth::or('form', Auth::and('header', 'queryWithNull'));
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getParameters());
        $this->assertEquals([], $request->getHeaders());
        $this->assertEquals('some/path', $request->getQueryUrl());
    }

    public function testFormOrHeaderOrQueryAuthWithMissingFields()
    {
        $request = new Request('some/path');
        $auth = Auth::or('form', Auth::or('header', 'queryWithNull'));
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getParameters());
        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path', $request->getQueryUrl());
    }

    public function testFormAndHeaderWithNullOrHeaderOrQueryWithNull()
    {
        $request = new Request('some/path');
        $auth = Auth::or(Auth::and('form', 'headerWithNull'), Auth::or('header', 'queryWithNull'));
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([], $request->getParameters());
        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path', $request->getQueryUrl());
    }

    public function testFormOrHeaderWithNullAndHeaderOrQueryWithNull()
    {
        $request = new Request('some/path');
        $auth = Auth::and(Auth::or('form', 'headerWithNull', 'formWithNull'), Auth::or('header', 'queryWithNull'));
        MockHelper::getCoreClient()->validateAuth($auth)->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getParameters());
        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path', $request->getQueryUrl());
    }

    public function testFormOrHeaderWithNullAndHeaderAndQueryWithNull()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required query field: token");

        $auth = Auth::and(Auth::or('form', 'headerWithNull'), Auth::and('header', 'queryWithNull'));
        MockHelper::getCoreClient()->validateAuth($auth);
    }

    public function testInvalidAuthName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("AuthManager not found with name: \"myAuth\"");

        MockHelper::getCoreClient()->validateAuth(Auth::or('myAuth'));
    }
}
