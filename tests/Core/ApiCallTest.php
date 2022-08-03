<?php

namespace CoreLib\Tests\Core;

use CoreDesign\Core\Format;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Http\RetryOption;
use CoreLib\Core\Request\Parameters\BodyParam;
use CoreLib\Core\Request\Parameters\FormParam;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\QueryParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Core\Request\RequestBuilder;
use CoreLib\Core\Response\Context;
use CoreLib\Core\Response\ErrorType;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Tests\Mocking\Other\MockException;
use CoreLib\Tests\Mocking\Other\MockException1;
use CoreLib\Tests\Mocking\Other\MockException3;
use CoreLib\Tests\Mocking\Types\MockApiResponse;
use CoreLib\Tests\Mocking\Types\MockRequest;
use Exception;
use PHPUnit\Framework\TestCase;

class ApiCallTest extends TestCase
{
    /**
     * @param string $query Just the query path of the url
     * @return array<string,string>
     */
    private static function convertQueryIntoArray(string $query): array
    {
        $array = [];
        foreach (explode('&', $query) as $item) {
            if (empty($item)) {
                continue;
            }
            $keyVal = explode('=', $item);
            $key = self::updateKeyForArray(urldecode($keyVal[0]), $array);
            $array[$key] = urldecode($keyVal[1]);
        }
        return $array;
    }

    private static function updateKeyForArray(string $key, array $array): string
    {
        if (key_exists($key, $array)) {
            return self::updateKeyForArray("$key*", $array);
        }
        return $key;
    }

    public function testSendWithConfig()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::PUT, '/2ndServer')
                ->server('Server2')
                ->auth('header')
                ->retryOption(RetryOption::ENABLE_RETRY))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(RequestMethod::PUT, $result->body['httpMethod']);
        $this->assertEquals('my/path/v2/2ndServer', $result->body['queryUrl']);
        $this->assertEquals('application/json', $result->body['headers']['Accept']);
        $this->assertEquals('headVal1', $result->body['headers']['additionalHead1']);
        $this->assertEquals('headVal2', $result->body['headers']['additionalHead2']);
        $this->assertEquals('someAuthToken', $result->body['headers']['token']);
        $this->assertEquals('accessToken', $result->body['headers']['authorization']);
        $this->assertStringStartsWith('my lang|1.*.*|', $result->body['headers']['user-agent']);
        $this->assertStringNotContainsString('{', $result->body['headers']['user-agent']);
    }

    public function testSendNoParams()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(RequestMethod::POST, $result->body['httpMethod']);
        $this->assertEquals('my/path/v1/simple/{tyu}', $result->body['queryUrl']);
        $this->assertEquals('application/json', $result->body['headers']['Accept']);
        $this->assertEquals('headVal1', $result->body['headers']['additionalHead1']);
        $this->assertEquals('headVal2', $result->body['headers']['additionalHead2']);
        $this->assertStringStartsWith('my lang|1.*.*|', $result->body['headers']['user-agent']);
        $this->assertStringNotContainsString('{', $result->body['headers']['user-agent']);
    }

    public function testSendTemplate()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(TemplateParam::init('tyu', 'val 01')))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('my/path/v1/simple/val+01', $result->body['queryUrl']);
    }

    public function testSendTemplateArray()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(TemplateParam::init('tyu', ['val 01','**sad&?N','v4'])))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('my/path/v1/simple/val+01/%2A%2Asad%26%3FN/v4', $result->body['queryUrl']);
    }

    public function testSendTemplateObject()
    {
        $mockObj = new MockClass([]);
        $mockObj->addAdditionalProperty('key', 'val 01');
        $mockObj->addAdditionalProperty('key2', 'v4');
        $mockObj2 = new MockClass([null,null]);
        $mockObj2->addAdditionalProperty('key3', '**sad&?N');
        $mockObj2->addAdditionalProperty('key4', 'v^^');
        $mockObj->addAdditionalProperty('key5', $mockObj2);
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(TemplateParam::init('tyu', $mockObj)))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('my/path/v1/simple//val+01/v4///%2A%2Asad%26%3FN/v%5E%5E', $result->body['queryUrl']);
    }

    public function testSendSingleQuery()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(QueryParam::init('key', 'val 01')))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray(explode('?', $result->body['queryUrl'])[1]);
        $this->assertEquals(['key' => 'val 01'], $query);
    }

    public function testSendSingleForm()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    FormParam::init('key', 'val 01'),
                    HeaderParam::init('content-type', 'myContentTypeHeader')
                ))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertArrayNotHasKey('content-type', $result->body['headers']);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertEquals(['key' => 'val 01'], $query);
    }

    public function testSendFileForm()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(FormParam::init('myFile', MockHelper::getFileWrapper())))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertStringEndsWith('testFile.txt', $query['myFile[name]']);
        $this->assertEquals('text/plain', $query['myFile[mime]']);
        $this->assertEquals('My Text', $query['myFile[postname]']);
    }

    public function testSendFileFormWithEncodingHeader()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    FormParam::init('myFile', MockHelper::getFileWrapper())->encodingHeader('content-type', 'image/png')
                ))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertStringEndsWith('testFile.txt', $query['myFile[name]']);
        $this->assertEquals('text/plain', $query['myFile[mime]']);
        $this->assertEquals('My Text', $query['myFile[postname]']);
    }

    public function testSendMultipleQuery()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    QueryParam::init('key A', 'val 1'),
                    QueryParam::init('keyB', new MockClass([])),
                    QueryParam::init('keyB2', [2,4]),
                    QueryParam::init('keyC', new MockClass([23, 24,'asad'])),
                    QueryParam::init('keyD', new MockClass([23, 24]))->unIndexed(),
                    QueryParam::init('keyE', new MockClass([true, false, null]))->plain(),
                    QueryParam::init('keyF', new MockClass(['A','B','C']))->commaSeparated(),
                    QueryParam::init('keyG', new MockClass(['A','B','C']))->tabSeparated(),
                    QueryParam::init('keyH', new MockClass(['A','B','C']))->pipeSeparated(),
                    QueryParam::init('keyI', new MockClass(['A','B', new MockClass([1])]))->pipeSeparated(),
                    QueryParam::init('keyJ', new MockClass(['innerKey1' => 'A', 'innerKey2' => 'B']))->pipeSeparated()
                ))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray(explode('?', $result->body['queryUrl'])[1]);
        $this->assertEquals([
            'key A' => 'val 1',
            'keyB2[0]' => '2',
            'keyB2[1]' => '4',
            'keyC[body][0]' => '23',
            'keyC[body][1]' => '24',
            'keyC[body][2]' => 'asad',
            'keyD[body][]' => '23',
            'keyD[body][]*' => '24',
            'keyE[body]' => 'true',
            'keyE[body]*' => 'false',
            'keyF[body]' => 'A,B,C',
            'keyG[body]' => 'A\\tB\\tC',
            'keyH[body]' => 'A|B|C',
            'keyI[body]' => 'A|B',
            'keyI[body][2][body]' => '1',
            'keyJ[body][innerKey1]' => 'A',
            'keyJ[body][innerKey2]' => 'B'
        ], $query);
    }

    public function testSendMultipleForm()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    FormParam::init('key A', 'val 1'),
                    FormParam::init('keyB', new MockClass([])),
                    FormParam::init('keyB2', [2,4]),
                    FormParam::init('keyB3', ['key1' => 2,'key2' => 4]),
                    FormParam::init('keyC', new MockClass([23, 24,'asad'])),
                    FormParam::init('keyD', new MockClass([23, 24]))->unIndexed(),
                    FormParam::init('keyE', new MockClass([23, 24, new MockClass([1])]))->unIndexed(),
                    FormParam::init('keyF', new MockClass([true, false, null]))->plain(),
                    FormParam::init('keyG', new MockClass(['innerKey1' => 'A', 'innerKey2' => 'B']))->plain()
                ))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertEquals([
            'key A' => 'val 1',
            'keyB2[0]' => '2',
            'keyB2[1]' => '4',
            'keyB3[key1]' => '2',
            'keyB3[key2]' => '4',
            'keyC[body][0]' => '23',
            'keyC[body][1]' => '24',
            'keyC[body][2]' => 'asad',
            'keyD[body][]' => '23',
            'keyD[body][]*' => '24',
            'keyE[body][]' => '23',
            'keyE[body][]*' => '24',
            'keyE[body][2][body][]' => '1',
            'keyF[body]' => 'true',
            'keyF[body]*' => 'false',
            'keyG[body][innerKey1]' => 'A',
            'keyG[body][innerKey2]' => 'B'
        ], $query);
    }

    public function testSendBodyParam()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(BodyParam::init('this is string')))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::SCALAR, $result->body['headers']['content-type']);
        $this->assertEquals('this is string', $result->body['body']);
    }

    public function testSendBodyParamFile()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(BodyParam::init(MockHelper::getFileWrapper())))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('application/octet-stream', $result->body['headers']['content-type']);
        $this->assertEquals('"This test file is created to test CoreFileWrapper functionality"', $result->body['body']);
    }

    public function testSendMultipleBodyParams()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    BodyParam::init('this is string', 'key1'),
                    BodyParam::init(new MockClass(['asad' => 'item1', 'ali' => 'item2']), 'key2')
                ))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::JSON, $result->body['headers']['content-type']);
        $this->assertEquals(
            '{"key1":"this is string","key2":{"body":{"asad":"item1","ali":"item2"}}}',
            $result->body['body']
        );
    }

    public function testSendXMLBodyParam()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(BodyParam::init('this is string'))
                ->bodyXml('myRoot'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals(
            '<?xml version="1.0"?>' . "\n" . '<myRoot>this is string</myRoot>' . "\n",
            $result->body['body']
        );
    }

    public function testSendXMLBodyParamModel()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(BodyParam::init(new MockClass([34,'asad'])))
                ->bodyXml('mockClass'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals("<?xml version=\"1.0\"?>\n" .
            "<mockClass attr=\"this is attribute\">" .
            "<body>34</body><body>asad</body><new1>this is new</new1><new2><entry key=\"key1\">val1</entry>" .
            "<entry key=\"key2\">val2</entry></new2></mockClass>\n", $result->body['body']);
    }

    public function testSendXMLNullBodyParam()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(BodyParam::init(null))
                ->bodyXml('myRoot'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals('<?xml version="1.0"?>' . "\n", $result->body['body']);
    }

    public function testSendXMLArrayBodyParam()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(BodyParam::init(['this is string', 345, false, null]))
                ->bodyXmlArray('myRoot', 'innerItem'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals(
            '<?xml version="1.0"?>' . "\n" . '<myRoot><innerItem>this is string</innerItem>' .
            '<innerItem>345</innerItem><innerItem>false</innerItem></myRoot>' . "\n",
            $result->body['body']
        );
    }

    public function testSendMultipleXMLBodyParams()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    BodyParam::init('this is string', 'key1'),
                    BodyParam::init('this is item 2', 'key2'),
                    BodyParam::init(null, 'key3')
                )
                ->bodyXmlMap('bodyRoot'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals(
            '<?xml version="1.0"?>' . "\n" . '<bodyRoot><entry key="key1">this is string</entry>' .
            '<entry key="key2">this is item 2</entry></bodyRoot>' . "\n",
            $result->body['body']
        );
    }

    public function testReceiveByWrongType()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('JsonMapper::mapClass() requires second argument to be a class name, ' .
            'MockClass given.');
        MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type('MockClass'))
            ->execute();
    }

    /**
     * @throws Exception
     */
    public function fakeSerializeBy($argument)
    {
        throw new Exception('Invalid argument found');
    }

    public function testReceiveByWrongDeserializerMethod()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('Invalid argument found');
        MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->deserializerMethod([$this, 'fakeSerializeBy']))
            ->execute();
    }

    public function testReceiveByWrongTypeGroup()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('Unable to map AnyOf (MockCla,string) on: ');
        MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->typeGroup('oneof(MockCla,string)'))
            ->execute();
    }

    public function testReceiveByAccurateTypeGroup()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->typeGroup('oneof(MockClass,string)'))
            ->execute();

        $this->assertInstanceOf(MockClass::class, $result);
    }

    public function testReceiveApiResponse()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}'))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->typeGroup('oneof(MockClass,string)')
                ->returnApiResponse())
            ->execute();
        $this->assertInstanceOf(MockApiResponse::class, $result);
        $this->assertInstanceOf(MockRequest::class, $result->getRequest());
        $this->assertInstanceOf(MockClass::class, $result->getResult());
        $this->assertStringContainsString('{"body":{"httpMethod":"Post","queryUrl":"my\/path\/v1\/simple\/{tyu}"' .
            ',"headers":{"additionalHead1":"headVal1","additionalHead2":"headVal2","user-agent":' .
            '"my lang|1.*.*|', $result->getBody());
        $this->assertStringContainsString(',"content-type":"text\/plain; charset=utf-8","Accept":"application\/json"' .
            '},"parameters":[],"body":null,"retryOption":"useGlobalSettings"},' .
            '"additionalProperties":[]}', $result->getBody());
        $this->assertEquals(['content-type' => 'application/json'], $result->getHeaders());
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertNull($result->getReasonPhrase());
    }

    public function testNullOn404()
    {
        $response = new MockResponse();
        $response->setStatusCode(404);
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        $result = MockHelper::globalResponseHandler()->nullOn404()->getResponse($context);
        $this->assertNull($result);
    }

    public function testGlobalMockException()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('Invalid Response.');
        $response = new MockResponse();
        $response->setStatusCode(500);
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        MockHelper::globalResponseHandler()->getResponse($context);
    }

    public function testGlobalMockException1()
    {
        $this->expectException(MockException1::class);
        $this->expectExceptionMessage('Exception num 1');
        $response = new MockResponse();
        $response->setStatusCode(400);
        $response->setBody([]);
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        MockHelper::globalResponseHandler()->getResponse($context);
    }

    public function testGlobalMockException3()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('Exception num 3');
        $response = new MockResponse();
        $response->setStatusCode(403);
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        MockHelper::globalResponseHandler()->getResponse($context);
    }

    public function testLocalMockException3()
    {
        $this->expectException(MockException3::class);
        $this->expectExceptionMessage('Local exception num 3');
        $response = new MockResponse();
        $response->setStatusCode(403);
        $response->setBody([]);
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        MockHelper::globalResponseHandler()
            ->throwErrorOn(403, ErrorType::init('Local exception num 3', MockException3::class))
            ->getResponse($context);
    }

    public function testScalarResponse()
    {
        $response = new MockResponse();
        $response->setBody("This is string");
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        $result = MockHelper::globalResponseHandler()
            ->getResponse($context);
        $this->assertEquals('This is string', $result);
    }

    public function testTypeXmlSimple()
    {
        $response = new MockResponse();
        $response->setRawBody("<?xml version=\"1.0\"?>\n<root>This is string</root>\n");
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        $result = MockHelper::globalResponseHandler()
            ->typeXml('string', 'root')
            ->getResponse($context);
        $this->assertEquals('This is string', $result);
    }

    public function testTypeXml()
    {
        $response = new MockResponse();
        $response->setRawBody("<?xml version=\"1.0\"?>\n" .
            "<mockClass attr=\"this is attribute\">\n" .
            "  <body>34</body>\n" .
            "  <body>asad</body>\n" .
            "  <new1>this is new</new1>\n" .
            "  <new2>\n" .
            "    <entry key=\"key1\">val1</entry>\n" .
            "    <entry key=\"key2\">val2</entry>\n" .
            "  </new2>\n" .
            "</mockClass>\n");
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        $result = MockHelper::globalResponseHandler()
            ->typeXml(MockClass::class, 'mockClass')
            ->getResponse($context);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(
            ["34","asad", "this is new", ["key1" => "val1", "key2" => "val2"], "this is attribute", null],
            $result->body
        );
    }

    public function testTypeXmlArray()
    {
        $response = new MockResponse();
        $response->setRawBody("<?xml version=\"1.0\"?>\n" .
            "<mockClassArray>\n" .
            "<mockClass attr=\"this is attribute\">\n" .
            "  <body>34</body>\n" .
            "  <body>asad</body>\n" .
            "  <new1>this is new</new1>\n" .
            "  <new2>\n" .
            "    <entry key=\"key1\">val1</entry>\n" .
            "    <entry key=\"key2\">val2</entry>\n" .
            "  </new2>\n" .
            "</mockClass>\n" .
            "</mockClassArray>\n");
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        $result = MockHelper::globalResponseHandler()
            ->typeXmlArray(MockClass::class, 'mockClassArray', 'mockClass')
            ->getResponse($context);
        $this->assertIsArray($result);
        $this->assertInstanceOf(MockClass::class, $result[0]);
        $this->assertEquals(
            ["34","asad", "this is new", ["key1" => "val1", "key2" => "val2"], "this is attribute", null],
            $result[0]->body
        );
    }

    public function testTypeXmlMap()
    {
        $response = new MockResponse();
        $response->setRawBody("<?xml version=\"1.0\"?>\n" .
            "<mockClassMap>\n" .
            "<entry key=\"mockClass\" attr=\"this is attribute\">\n" .
            "  <body>34</body>\n" .
            "  <body>asad</body>\n" .
            "  <new1>this is new</new1>\n" .
            "  <new2>\n" .
            "    <entry key=\"key1\">val1</entry>\n" .
            "    <entry key=\"key2\">val2</entry>\n" .
            "  </new2>\n" .
            "</entry>\n" .
            "</mockClassMap>\n");
        $context = new Context(MockHelper::getCoreConfig()->getGlobalRequest(), $response, MockHelper::getCoreConfig());
        $result = MockHelper::globalResponseHandler()
            ->typeXmlMap(MockClass::class, 'mockClassMap')
            ->getResponse($context);
        $this->assertIsArray($result);
        $this->assertInstanceOf(MockClass::class, $result['mockClass']);
        $this->assertEquals(
            ["34","asad", "this is new", ["key1" => "val1", "key2" => "val2"], "this is attribute", null],
            $result['mockClass']->body
        );
    }
}
