<?php

namespace CoreLib\Tests\Authentication;

use CoreDesign\Core\Request\RequestMethod;
use CoreLib\Authentication\Auth;
use CoreLib\Core\Request\Request;
use CoreLib\Tests\Mocking\MockHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    public function testHeaderAuth()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth = Auth::or('header')->withAuthManagers($authManagers);
        $auth->validate();
        $auth->apply($request);

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

        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::or('headerWithNull')->withAuthManagers($authManagers);
        $auth->validate();
    }

    public function testHeaderOrQueryAuth()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::or('header', 'query')->withAuthManagers($authManagers);
        $auth->validate();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path?token=someAuthToken&authorization=accessToken', $request->getQueryUrl());
    }

    public function testHeaderWithMissingFieldOrQueryAuth()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth = Auth::or('headerWithNull', 'query')->withAuthManagers($authManagers);
        $auth->validate();
        $auth->apply($request);

        $this->assertEquals([], $request->getHeaders());
        $this->assertEquals('some/path?token=someAuthToken&authorization=accessToken', $request->getQueryUrl());
    }

    public function testHeaderOrQueryAuthWithMissingFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required auth credentials:" .
            "\n-> Missing required header field: authorization" .
            "\n-> Missing required query field: token");

        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::or('headerWithNull', 'queryWithNull')->withAuthManagers($authManagers);
        $auth->validate();
    }

    public function testHeaderAndQueryAuth()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::and('header', 'query')->withAuthManagers($authManagers);
        $auth->validate();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth->apply($request);

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

        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::and('headerWithNull', 'query')->withAuthManagers($authManagers);
        $auth->validate();
    }

    public function testHeaderAndQueryAuthWithMissingFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required header field: authorization");

        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::and('headerWithNull', 'queryWithNull')->withAuthManagers($authManagers);
        $auth->validate();
    }

    public function testFormOrHeaderAndQueryAuthWithMissingFields()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::or('form', Auth::and('header', 'queryWithNull'))->withAuthManagers($authManagers);
        $auth->validate();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth->apply($request);

        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getParameters());
        $this->assertEquals([], $request->getHeaders());
        $this->assertEquals('some/path', $request->getQueryUrl());
    }

    public function testFormOrHeaderOrQueryAuthWithMissingFields()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::or('form', Auth::or('header', 'queryWithNull'))->withAuthManagers($authManagers);
        $auth->validate();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth->apply($request);

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
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::or(Auth::and('form', 'headerWithNull'), Auth::or('header', 'queryWithNull'))
            ->withAuthManagers($authManagers);
        $auth->validate();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth->apply($request);

        $this->assertEquals([], $request->getParameters());
        $this->assertEquals([
            'token' => 'someAuthToken',
            'authorization' => 'accessToken'
        ], $request->getHeaders());
        $this->assertEquals('some/path', $request->getQueryUrl());
    }

    public function testFormOrHeaderWithNullAndHeaderOrQueryWithNull()
    {
        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::and(Auth::or('form', 'headerWithNull', 'formWithNull'), Auth::or('header', 'queryWithNull'))
            ->withAuthManagers($authManagers);
        $auth->validate();
        $request = new Request(RequestMethod::GET, 'some/path');
        $auth->apply($request);

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

        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        $auth = Auth::and(Auth::or('form', 'headerWithNull'), Auth::and('header', 'queryWithNull'))
            ->withAuthManagers($authManagers);
        $auth->validate();
    }

    public function testInvalidAuthName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("AuthManager not found with name: \"myAuth\"");

        $authManagers = MockHelper::getCoreConfig()->getAuthManagers();
        Auth::or('myAuth')->withAuthManagers($authManagers);
    }
}
