<?php

namespace CoreLib\Tests;

use CoreDesign\Core\Format;
use CoreDesign\Core\Request\RequestArraySerialization;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Http\RetryOption;
use CoreLib\Core\Request\Parameters\BodyParam;
use CoreLib\Core\Request\Parameters\FormParam;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\QueryParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Core\Request\RequestBuilder;
use CoreLib\Core\Response\Context;
use CoreLib\Core\Response\Types\ErrorType;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Tests\Mocking\Other\MockException;
use CoreLib\Tests\Mocking\Other\MockException1;
use CoreLib\Tests\Mocking\Other\MockException3;
use CoreLib\Tests\Mocking\Types\MockApiResponse;
use CoreLib\Tests\Mocking\Types\MockRequest;
use CURLFile;
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

    public function testCollectedBodyParams()
    {
        $request = RequestBuilder::init(RequestMethod::POST, '/some/path')
            ->parameters(BodyParam::initFromCollected('key1', null))
            ->build(MockHelper::getCoreClient());
        $this->assertNull($request->getBody());

        $options = ['key1' => true, 'key2' => 'some string', 'key3' => 23];
        $request = RequestBuilder::init(RequestMethod::POST, '/some/path')
            ->parameters(BodyParam::initFromCollected('key1', $options))
            ->build(MockHelper::getCoreClient());
        $this->assertEquals('true', $request->getBody());

        $request = RequestBuilder::init(RequestMethod::POST, '/some/path')
            ->parameters(
                BodyParam::initWrappedFromCollected('key1', $options),
                BodyParam::initWrappedFromCollected('key3', $options)
            )
            ->build(MockHelper::getCoreClient());
        $this->assertEquals('{"key1":true,"key3":23}', $request->getBody());

        $request = RequestBuilder::init(RequestMethod::POST, '/some/path')
            ->parameters(BodyParam::initFromCollected('key4', $options, 'MyConstant'))
            ->build(MockHelper::getCoreClient());
        $this->assertEquals('MyConstant', $request->getBody());
    }

    public function testCollectedFormParams()
    {
        $options = ['key1' => true, 'key2' => 'some string', 'key3' => 23];

        $request = RequestBuilder::init(RequestMethod::POST, '/some/path')
            ->parameters(
                FormParam::initFromCollected('key1', $options),
                FormParam::initFromCollected('key3', $options),
                FormParam::initFromCollected('key4', $options, 'MyConstant'),
                FormParam::initFromCollected('key2', $options, 'new string')
            )
            ->build(MockHelper::getCoreClient());
        $this->assertNull($request->getBody());
        $this->assertEquals([
            'key1' => 'true',
            'key2' => 'some string',
            'key3' => 23,
            'key4' => 'MyConstant'
        ], $request->getParameters());
        $this->assertEquals([
            'key1' => 'key1=true',
            'key2' => 'key2=some+string',
            'key3' => 'key3=23',
            'key4' => 'key4=MyConstant'
        ], $request->getEncodedParameters());
        $this->assertEquals([], $request->getMultipartParameters());
    }

    public function testCollectedHeaderParams()
    {
        $options = ['key1' => true, 'key2' => 'some string', 'key3' => 23];

        $request = RequestBuilder::init(RequestMethod::POST, '/some/path')
            ->parameters(
                HeaderParam::initFromCollected('key1', $options),
                HeaderParam::initFromCollected('key3', $options),
                HeaderParam::initFromCollected('key4', $options, 'MyConstant'),
                HeaderParam::initFromCollected('key2', $options, 'new string')
            )
            ->additionalHeaderParams(['key5' => 1234.4321])
            ->build(MockHelper::getCoreClient());
        $this->assertEquals(true, $request->getHeaders()['key1']);
        $this->assertEquals('some string', $request->getHeaders()['key2']);
        $this->assertEquals(23, $request->getHeaders()['key3']);
        $this->assertEquals('MyConstant', $request->getHeaders()['key4']);
        $this->assertEquals(890.098, $request->getHeaders()['key5']);
    }

    public function testCollectedQueryParams()
    {
        $options = ['key1' => true, 'key2' => 'some string', 'key3' => 23];

        $request = RequestBuilder::init(RequestMethod::POST, '/path')
            ->parameters(
                QueryParam::initFromCollected('key1', $options),
                QueryParam::initFromCollected('key3', $options),
                QueryParam::initFromCollected('key4', $options, 'MyConstant'),
                QueryParam::initFromCollected('key2', $options, 'new string')
            )
            ->build(MockHelper::getCoreClient());
        $this->assertEquals(
            'http://my/path:3000/v1/path?key1=true&key3=23&key4=MyConstant&key2=some+string',
            $request->getQueryUrl()
        );
    }

    public function testCollectedTemplateParams()
    {
        $options = ['key1' => true, 'key2' => 'some string', 'key3' => 23];

        $request = RequestBuilder::init(RequestMethod::POST, '/{key1}/{key2}/{key3}/{key4}')
            ->parameters(
                TemplateParam::initFromCollected('key1', $options),
                TemplateParam::initFromCollected('key3', $options),
                TemplateParam::initFromCollected('key4', $options, 'MyConstant'),
                TemplateParam::initFromCollected('key2', $options, 'new string')
            )
            ->build(MockHelper::getCoreClient());
        $this->assertEquals('http://my/path:3000/v1/true/some+string/23/MyConstant', $request->getQueryUrl());
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
        $this->assertEquals('https://my/path/v2/2ndServer', $result->body['queryUrl']);
        $this->assertEquals('application/json', $result->body['headers']['Accept']);
        $this->assertEquals('headVal1', $result->body['headers']['additionalHead1']);
        $this->assertEquals('headVal2', $result->body['headers']['additionalHead2']);
        $this->assertEquals('someAuthToken', $result->body['headers']['token']);
        $this->assertEquals('accessToken', $result->body['headers']['authorization']);
        $this->assertStringStartsWith('my lang|1.*.*|', $result->body['headers']['user-agent']);
        $this->assertStringNotContainsString('{', $result->body['headers']['user-agent']);
    }

    public function testSendWithoutContentType()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->disableContentType())
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        self::assertArrayNotHasKey('content-type', $result->body['headers']);
        self::assertArrayNotHasKey('Accept', $result->body['headers']);
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
        $this->assertEquals('http://my/path:3000/v1/simple/{tyu}', $result->body['queryUrl']);
        $this->assertEquals('application/json', $result->body['headers']['Accept']);
        $this->assertEquals('text/plain; charset=utf-8', $result->body['headers']['content-type']);
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
        $this->assertEquals('http://my/path:3000/v1/simple/val+01', $result->body['queryUrl']);
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
        $this->assertEquals('http://my/path:3000/v1/simple/val+01/%2A%2Asad%26%3FN/v4', $result->body['queryUrl']);
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
        $this->assertEquals(
            'http://my/path:3000/v1/simple//val+01/v4///%2A%2Asad%26%3FN/v%5E%5E',
            $result->body['queryUrl']
        );
    }

    public function testSendSingleQuery()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(QueryParam::init('key', 'val 01'))
                ->additionalQueryParams(null))
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
                )
                ->additionalFormParams(null))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('myContentTypeHeader', $result->body['headers']['content-type']);
        $this->assertNull($result->body['body']);
        $this->assertEquals(['key' => 'val 01'], $result->body['parameters']);
        $this->assertEquals(['key' => 'key=val+01'], $result->body['parametersEncoded']);
        $this->assertEquals([], $result->body['parametersMultipart']);
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
        $this->assertEquals([], $result->body['parametersEncoded']);
        $file = $result->body['parametersMultipart']['myFile'];
        $this->assertInstanceOf(CURLFile::class, $file);
        $this->assertStringEndsWith('testFile.txt', $file->getFilename());
        $this->assertEquals('text/plain', $file->getMimeType());
        $this->assertEquals('My Text', $file->getPostFilename());
        $this->assertEquals($file, $result->body['parameters']['myFile']);
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
        $this->assertEquals([], $result->body['parametersEncoded']);
        $file = $result->body['parametersMultipart']['myFile'];
        $this->assertInstanceOf(CURLFile::class, $file);
        $this->assertStringEndsWith('testFile.txt', $file->getFilename());
        $this->assertEquals('text/plain', $file->getMimeType());
        $this->assertEquals('My Text', $file->getPostFilename());
        $this->assertEquals($file, $result->body['parameters']['myFile']);
    }

    public function testSendFileFormWithOtherTypes()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    FormParam::init('myFile', MockHelper::getFileWrapper()),
                    FormParam::init('key', 'val 01'),
                    FormParam::init('special', ['%^&&*^?.. + @214', true])
                ))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals([
            'key' => 'key=val+01',
            'special' => 'special%5B0%5D=%25%5E%26%26%2A%5E%3F..+%2B+%40214&special%5B1%5D=true'
        ], $result->body['parametersEncoded']);
        $this->assertEquals(1, count($result->body['parametersMultipart']));
        $file = $result->body['parametersMultipart']['myFile'];
        $this->assertInstanceOf(CURLFile::class, $file);
        $this->assertStringEndsWith('testFile.txt', $file->getFilename());
        $this->assertEquals('text/plain', $file->getMimeType());
        $this->assertEquals('My Text', $file->getPostFilename());
        $this->assertEquals([
            'myFile' => $file,
            'key' => 'val 01',
            'special' => ['%^&&*^?.. + @214', 'true']
        ], $result->body['parameters']);
    }

    public function testSendMultipleQuery()
    {
        $additionalQueryParams = [
            'keyH' => [2,4],
            'newKey' => 'asad'
        ];
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
                )
                ->additionalQueryParams($additionalQueryParams))
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
            'keyJ[body][innerKey2]' => 'B',
            'keyH[0]' => '2',
            'keyH[1]' => '4',
            'newKey' => 'asad'
        ], $query);
    }

    public function testSendMultipleForm()
    {
        $additionalFormParams = [
            'keyH' => [2,4],
            'newKey' => 'asad'
        ];
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
                )
                ->additionalFormParams($additionalFormParams, RequestArraySerialization::UN_INDEXED))
            ->responseHandler(MockHelper::globalResponseHandler()
                ->type(MockClass::class))
            ->execute();
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals([
            'key A' => 'key+A=val+1',
            'keyB2' => 'keyB2%5B0%5D=2&keyB2%5B1%5D=4',
            'keyB3' => 'keyB3%5Bkey1%5D=2&keyB3%5Bkey2%5D=4',
            'keyC' => 'keyC%5Bbody%5D%5B0%5D=23&keyC%5Bbody%5D%5B1%5D=24&keyC%5Bbody%5D%5B2%5D=asad',
            'keyD' => 'keyD%5Bbody%5D%5B%5D=23&keyD%5Bbody%5D%5B%5D=24',
            'keyE' => 'keyE%5Bbody%5D%5B%5D=23&keyE%5Bbody%5D%5B%5D=24&keyE%5Bbody%5D%5B2%5D%5Bbody%5D%5B%5D=1',
            'keyF' => 'keyF%5Bbody%5D=true&keyF%5Bbody%5D=false',
            'keyG' => 'keyG%5Bbody%5D%5BinnerKey1%5D=A&keyG%5Bbody%5D%5BinnerKey2%5D=B',
            'keyH' => 'keyH%5B%5D=2&keyH%5B%5D=4',
            'newKey' => 'newKey=asad'
        ], $result->body['parametersEncoded']);
        $this->assertEquals([
            'key A' => 'val 1',
            'keyB2' => [2,4],
            'keyB3' => ['key1' => 2,'key2' => 4],
            'keyC' => ['body' => [23, 24,'asad']],
            'keyD' => ['body' => [23, 24]],
            'keyE' => ['body' => [23, 24, ['body' => [1]]]],
            'keyF' => ['body' => ['true', 'false', null]],
            'keyG' => ['body' => ['innerKey1' => 'A', 'innerKey2' => 'B']],
            'keyH' => [2,4],
            'newKey' => 'asad'
        ], $result->body['parameters']);
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
        $this->assertEquals('This test file is created to test CoreFileWrapper functionality', $result->body['body']);
    }

    public function testSendMultipleBodyParams()
    {
        $result = MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(
                    BodyParam::initWrapped('key1', 'this is string'),
                    BodyParam::initWrapped('key2', new MockClass(['asad' => 'item1', 'ali' => 'item2']))
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
                    BodyParam::initWrapped('key1', 'this is string'),
                    BodyParam::initWrapped('key2', 'this is item 2'),
                    BodyParam::initWrapped('key3', null)
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
        $this->assertStringContainsString('{"body":{"httpMethod":"Post","queryUrl":"http:\/\/my\/path:3000\/v1' .
            '\/simple\/{tyu}","headers":{"additionalHead1":"headVal1","additionalHead2":"headVal2","user-agent":' .
            '"my lang|1.*.*|', $result->getBody());
        $this->assertStringContainsString(',"content-type":"text\/plain; charset=utf-8","Accept":"application\/json"' .
            '},"parameters":[],"parametersEncoded":[],"parametersMultipart":[],"body":null,' .
            '"retryOption":"useGlobalSettings"},"additionalProperties":[]}', $result->getBody());
        $this->assertEquals(['content-type' => 'application/json'], $result->getHeaders());
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertNull($result->getReasonPhrase());
    }

    public function testNullOn404()
    {
        $response = new MockResponse();
        $response->setStatusCode(404);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        $result = MockHelper::globalResponseHandler()->nullOn404()->getResult($context);
        $this->assertNull($result);
    }

    public function testGlobalMockException()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('HTTP Response Not OK');
        $response = new MockResponse();
        $response->setStatusCode(500);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        MockHelper::globalResponseHandler()->getResult($context);
    }

    public function testGlobalMockException1()
    {
        $this->expectException(MockException1::class);
        $this->expectExceptionMessage('Exception num 1');
        $response = new MockResponse();
        $response->setStatusCode(400);
        $response->setBody([]);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        MockHelper::globalResponseHandler()->getResult($context);
    }

    public function testGlobalMockException3()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('Exception num 3');
        $response = new MockResponse();
        $response->setStatusCode(403);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        MockHelper::globalResponseHandler()->getResult($context);
    }

    public function testLocalMockException3()
    {
        $this->expectException(MockException3::class);
        $this->expectExceptionMessage('Local exception num 3');
        $response = new MockResponse();
        $response->setStatusCode(403);
        $response->setBody([]);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        MockHelper::globalResponseHandler()
            ->throwErrorOn(403, ErrorType::init('Local exception num 3', MockException3::class))
            ->getResult($context);
    }

    public function testDefaultMockException1()
    {
        $this->expectException(MockException1::class);
        $this->expectExceptionMessage('Default exception');
        $response = new MockResponse();
        $response->setStatusCode(500);
        $response->setBody([]);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        MockHelper::globalResponseHandler()
            ->throwErrorOn(403, ErrorType::init('local exception num 3', MockException3::class))
            ->throwErrorOn(0, ErrorType::init('Default exception', MockException1::class))
            ->getResult($context);
    }

    public function testDefaultExceptionMessage()
    {
        $this->expectException(MockException::class);
        $this->expectExceptionMessage('Default exception');
        $response = new MockResponse();
        $response->setStatusCode(500);
        $response->setBody([]);
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        MockHelper::globalResponseHandler()
            ->throwErrorOn(403, ErrorType::init('local exception num 3', MockException3::class))
            ->throwErrorOn(0, ErrorType::init('Default exception'))
            ->getResult($context);
    }

    public function testScalarResponse()
    {
        $response = new MockResponse();
        $response->setBody("This is string");
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        $result = MockHelper::globalResponseHandler()
            ->getResult($context);
        $this->assertEquals('This is string', $result);
    }

    public function testTypeXmlSimple()
    {
        $response = new MockResponse();
        $response->setRawBody("<?xml version=\"1.0\"?>\n<root>This is string</root>\n");
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        $result = MockHelper::globalResponseHandler()
            ->typeXml('string', 'root')
            ->getResult($context);
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
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        $result = MockHelper::globalResponseHandler()
            ->typeXml(MockClass::class, 'mockClass')
            ->getResult($context);
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
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        $result = MockHelper::globalResponseHandler()
            ->typeXmlArray(MockClass::class, 'mockClassArray', 'mockClass')
            ->getResult($context);
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
        $context = new Context(MockHelper::getCoreClient()->getGlobalRequest(), $response, MockHelper::getCoreClient());
        $result = MockHelper::globalResponseHandler()
            ->typeXmlMap(MockClass::class, 'mockClassMap')
            ->getResult($context);
        $this->assertIsArray($result);
        $this->assertInstanceOf(MockClass::class, $result['mockClass']);
        $this->assertEquals(
            ["34","asad", "this is new", ["key1" => "val1", "key2" => "val2"], "this is attribute", null],
            $result['mockClass']->body
        );
    }
}
