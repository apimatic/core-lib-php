<?php

namespace CoreLib\Tests;

use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\RetryOption;
use CoreLib\Core\Request\Request;
use CoreLib\Core\Response\Context;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testChildOfCoreCallback()
    {
        $callback = MockHelper::getCallback();
        $callback->setOnBeforeRequest(function (MockClass $val): void {
            $this->assertEquals(RequestMethod::GET, $val->body[0]);
            $this->assertEquals('some/path', $val->body[1]);
            $this->assertEquals([], $val->body[2]);
            $this->assertEquals([], $val->body[3]);
            $this->assertEquals(null, $val->body[4]);
            $this->assertEquals(RetryOption::USE_GLOBAL_SETTINGS, $val->body[5]);
        });
        $callback->setOnAfterRequest(function (MockClass $val): void {
            $this->assertInstanceOf(RequestInterface::class, $val->body[0]);
            $this->assertInstanceOf(ResponseInterface::class, $val->body[1]);
        });
        $this->assertNotNull($callback->getOnBeforeRequest());
        $this->assertNotNull($callback->getOnAfterRequest());

        $request = new Request(RequestMethod::GET, 'some/path');
        $response = MockHelper::getResponse();
        $context = new Context($request, $response);

        $callback->callOnBeforeWithConversion($request, MockHelper::getCoreConfig()->getConverter());
        $callback->callOnAfterWithConversion($context, MockHelper::getCoreConfig()->getConverter());
    }

    public function testCallbackCatcher()
    {
        $callback = MockHelper::getCallbackCatcher();
        $request = new Request(RequestMethod::GET, 'some/path');
        $this->assertNull($callback->getOnBeforeRequest());
        $callback->callOnBeforeWithConversion($request, MockHelper::getCoreConfig()->getConverter());

        $response = MockHelper::getResponse();
        $context = new Context($request, $response);
        $callback->callOnAfterWithConversion($context, MockHelper::getCoreConfig()->getConverter());

        $this->assertEquals($request, $callback->getRequest());
        $this->assertEquals($response, $callback->getResponse());
    }

    public function testChildOfCoreFileWrapper()
    {
        $fileWrapper = MockHelper::getFileWrapper();

        $this->assertEquals('text/plain', $fileWrapper->getMimeType());
        $this->assertEquals('My Text', $fileWrapper->getFilename());
        $this->assertEquals(
            'This test file is created to test CoreFileWrapper functionality',
            $fileWrapper->getContent()
        );
        $curlFile = $fileWrapper->createCurlFileInstance('application/octet-stream');
        $this->assertStringEndsWith('testFile.txt', $curlFile->getFilename());
        $this->assertEquals('text/plain', $curlFile->getMimeType());
        $this->assertEquals('My Text', $curlFile->getPostFilename());
    }
}
