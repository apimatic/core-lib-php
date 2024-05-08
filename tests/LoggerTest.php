<?php

namespace Core\Tests;

use Core\Logger\ApiLogger;
use Core\Logger\ConsoleLogger;
use Core\Request\Parameters\BodyParam;
use Core\Request\Parameters\FormParam;
use Core\Request\Parameters\HeaderParam;
use Core\Request\Parameters\QueryParam;
use Core\Request\Request;
use Core\Tests\Mocking\Logger\LogEntry;
use Core\Tests\Mocking\Logger\MockPrinter;
use Core\Tests\Mocking\MockHelper;
use Core\Tests\Mocking\Response\MockResponse;
use Core\Utils\CoreHelper;
use CoreInterfaces\Core\Format;
use CoreInterfaces\Core\Logger\ApiLoggerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LoggerTest extends TestCase
{
    public function testLogLevels()
    {
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::INFO));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::DEBUG));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::NOTICE));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::ERROR));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::EMERGENCY));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::ALERT));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::CRITICAL));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::WARNING));
        MockHelper::getMockLogger()->assertLastEntries($this->logAndGetEntry(LogLevel::INFO));

        $loggedEntriesCount = MockHelper::getMockLogger()->countEntries();
        $this->logAndGetEntry('__unknown__');

        // Making sure it didn't log any entry (increased entry count)
        $this->assertEquals($loggedEntriesCount, MockHelper::getMockLogger()->countEntries());
    }

    private function logAndGetEntry(string $level): LogEntry
    {
        $logEntry = new LogEntry($level, 'someMessage', []);
        MockHelper::getLoggingConfiguration($level)->logMessage($logEntry->message, $logEntry->context);
        return $logEntry;
    }

    public function testConsoleLogger()
    {
        $printer = new MockPrinter();
        $consoleLoggerMock = new ConsoleLogger([$printer, 'printMessage']);
        $loggingConfig = MockHelper::getLoggingConfiguration(null, null, null, null, $consoleLoggerMock);
        $apiLogger = new ApiLogger($loggingConfig);
        $apiLogger->logRequest(new Request('https://some/path', MockHelper::getClient()));

        $this->assertEquals(["%s: %s\n", 'info', 'Request Get https://some/path '], $printer->args);
    }

    public function testDefaultLoggingConfiguration()
    {
        $apiLogger = MockHelper::getClient()->getApiLogger();
        $this->assertInstanceOf(ApiLoggerInterface::class, $apiLogger);

        $request = new Request('https://some/path');
        $response = MockHelper::getClient()->getHttpClient()->execute($request);
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path',
                'contentType' => null
            ])
        );
        $apiLogger->logResponse($response);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Response {statusCode} {contentLength} {contentType}', [
                'statusCode' => 200,
                'contentLength' => null,
                'contentType' => 'application/json'
            ])
        );
    }

    public function testLoggingRequestShouldIncludeInQuery()
    {
        $requestParams = MockHelper::getClient()->validateParameters([
            QueryParam::init('key', 'value')
        ]);
        $request = new Request('https://some/path', MockHelper::getClient(), $requestParams);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            null,
            null,
            MockHelper::getRequestLoggingConfiguration(true)
        ));
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path?key=value',
                'contentType' => null
            ])
        );
    }

    public function testLoggingRequestContentType()
    {
        $requestParams = MockHelper::getClient()->validateParameters([
            HeaderParam::init('Content-Type', 'my-content-type')
        ]);
        $request = new Request('https://some/path', MockHelper::getClient(), $requestParams);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration());
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path',
                'contentType' => 'my-content-type'
            ])
        );
    }

    public function testLoggingRequestFileAsBody()
    {
        $requestParams = MockHelper::getClient()->validateParameters([
            BodyParam::init(MockHelper::getFileWrapper()),
        ]);
        $request = new Request('https://some/path', MockHelper::getClient(), $requestParams);
        $request->setBodyFormat(Format::JSON, [CoreHelper::class, 'serialize']);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            null,
            null,
            MockHelper::getRequestLoggingConfiguration(
                false,
                true
            )
        ));
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path',
                'contentType' => 'application/octet-stream'
            ]),
            new LogEntry('info', 'Request Body {body}', [
                'body' => 'This test file is created to test CoreFileWrapper functionality'
            ])
        );
    }

    public function testLoggingRequestBody()
    {
        $requestParams = MockHelper::getClient()->validateParameters([
            BodyParam::init([
                'key' => 'value'
            ]),
        ]);
        $request = new Request('https://some/path', MockHelper::getClient(), $requestParams);
        $request->setBodyFormat(Format::JSON, [CoreHelper::class, 'serialize']);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            null,
            null,
            MockHelper::getRequestLoggingConfiguration(
                false,
                true
            )
        ));
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path',
                'contentType' => 'application/json'
            ]),
            new LogEntry('info', 'Request Body {body}', [
                'body' => '{"key":"value"}'
            ])
        );
    }

    public function testLoggingRequestFormParams()
    {
        $requestParams = MockHelper::getClient()->validateParameters([
            FormParam::init('key', 'value')
        ]);
        $request = new Request('https://some/path', MockHelper::getClient(), $requestParams);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            null,
            null,
            MockHelper::getRequestLoggingConfiguration(
                false,
                true
            )
        ));
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path',
                'contentType' => null
            ]),
            new LogEntry('info', 'Request Body {body}', [
                'body' => [
                    'key' => 'value'
                ]
            ])
        );
    }

    public function testLoggingRequestHeaders()
    {
        $requestParams = MockHelper::getClient()->validateParameters([
            HeaderParam::init('Content-Type', 'my-content-type'),
            HeaderParam::init('HeaderA', 'value A'),
            HeaderParam::init('HeaderB', 'value B'),
            HeaderParam::init('Expires', '2345ms')
        ]);
        $request = new Request('https://some/path', MockHelper::getClient(), $requestParams);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            null,
            null,
            MockHelper::getRequestLoggingConfiguration(
                false,
                false,
                true
            )
        ));
        $apiLogger->logRequest($request);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Request {method} {url} {contentType}', [
                'method' => 'Get',
                'url' => 'https://some/path',
                'contentType' => 'my-content-type'
            ]),
            new LogEntry('info', 'Request Headers {headers}', [
                'headers' => [
                    'Content-Type' => 'my-content-type',
                    'HeaderA' => '**Redacted**',
                    'HeaderB' => '**Redacted**',
                    'Expires' => '2345ms',
                    'key5' => '**Redacted**'
                ]
            ])
        );
    }

    public function testLoggingResponseBody()
    {
        $response = new MockResponse();
        $response->setStatusCode(200);
        $response->setBody([
            'key' => 'value'
        ]);
        $response->setHeaders([
            'content-type' => 'application/json',
            'content-length' => '45'
        ]);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            null,
            null,
            null,
            MockHelper::getResponseLoggingConfiguration(true)
        ));
        $apiLogger->logResponse($response);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('info', 'Response {statusCode} {contentLength} {contentType}', [
                'statusCode' => 200,
                'contentLength' => '45',
                'contentType' => 'application/json'
            ]),
            new LogEntry('info', 'Response Body {body}', [
                'body' => '{"key":"value"}'
            ])
        );
    }

    public function testLoggingResponseHeaders()
    {
        $response = new MockResponse();
        $response->setStatusCode(400);
        $response->setHeaders([
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ]);
        $apiLogger = new ApiLogger(MockHelper::getLoggingConfiguration(
            LogLevel::ERROR,
            null,
            null,
            MockHelper::getResponseLoggingConfiguration(
                false,
                true
            )
        ));
        $apiLogger->logResponse($response);
        MockHelper::getMockLogger()->assertLastEntries(
            new LogEntry('error', 'Response {statusCode} {contentLength} {contentType}', [
                'statusCode' => 400,
                'contentLength' => null,
                'contentType' => 'my-content-type'
            ]),
            new LogEntry('error', 'Response Headers {headers}', [
                'headers' => [
                    'Content-Type' => 'my-content-type',
                    'HeaderA' => '**Redacted**',
                    'HeaderB' => '**Redacted**',
                    'Expires' => '2345ms'
                ]
            ])
        );
    }

    public function testLoggableHeaders()
    {
        $responseConfig = MockHelper::getResponseLoggingConfiguration(false, true);
        $headers = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $expectedHeaders = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => '**Redacted**',
            'HeaderB' => '**Redacted**',
            'Expires' => '2345ms'
        ];
        $this->assertEquals($expectedHeaders, $responseConfig->getLoggableHeaders($headers, true));
    }

    public function testAllUnMaskedLoggableHeaders()
    {
        $responseConfig = MockHelper::getResponseLoggingConfiguration(false, true);
        $headers = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $this->assertEquals($headers, $responseConfig->getLoggableHeaders($headers, false));
    }

    public function testIncludedLoggableHeaders()
    {
        $responseConfig = MockHelper::getResponseLoggingConfiguration(
            false,
            true,
            ['HeaderB', 'Expires']
        );
        $headers = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $expectedHeaders = [
            'HeaderB' => '**Redacted**',
            'Expires' => '2345ms'
        ];
        $this->assertEquals($expectedHeaders, $responseConfig->getLoggableHeaders($headers, true));
    }

    public function testExcludedLoggableHeaders()
    {
        $responseConfig = MockHelper::getResponseLoggingConfiguration(
            false,
            true,
            [],
            ['HeaderB', 'Expires']
        );
        $headers = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $expectedHeaders = [
            'HeaderA' => '**Redacted**',
            'Content-Type' => 'my-content-type',
        ];
        $this->assertEquals($expectedHeaders, $responseConfig->getLoggableHeaders($headers, true));
    }

    public function testIncludeAndExcludeLoggableHeaders()
    {
        $responseConfig = MockHelper::getResponseLoggingConfiguration(
            false,
            true,
            ['HEADERB', 'EXPIRES'],
            ['EXPIRES']
        );
        $headers = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $expectedHeaders = [
            'HeaderB' => '**Redacted**',
            'Expires' => '2345ms'
        ];
        // If both include and exclude headers are provided then only includeHeaders will work
        $this->assertEquals($expectedHeaders, $responseConfig->getLoggableHeaders($headers, true));
    }

    public function testUnMaskedLoggableHeaders()
    {
        $responseConfig = MockHelper::getResponseLoggingConfiguration(
            false,
            true,
            [],
            [],
            ['HeaderB']
        );
        $headers = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => 'value A',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $expectedHeaders = [
            'Content-Type' => 'my-content-type',
            'HeaderA' => '**Redacted**',
            'HeaderB' => 'value B',
            'Expires' => '2345ms'
        ];
        $this->assertEquals($expectedHeaders, $responseConfig->getLoggableHeaders($headers, true));
    }
}
