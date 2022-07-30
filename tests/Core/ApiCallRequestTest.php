<?php

namespace CoreLib\Tests\Core;

use CoreDesign\Core\Format;
use CoreDesign\Core\Request\RequestMethod;
use CoreLib\Core\Request\Parameters\BodyParam;
use CoreLib\Core\Request\Parameters\FormParam;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\QueryParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Core\Request\RequestBuilder;
use CoreLib\Tests\Mocking\Core\MockApiCalls;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use PHPUnit\Framework\TestCase;

class ApiCallRequestTest extends TestCase
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
        $result = MockApiCalls::sendRequestWithOtherConfig();
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
        $result = MockApiCalls::sendRequestWithParams();
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
        $result = MockApiCalls::sendRequestWithParams(TemplateParam::init('tyu', 'val 01'));
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('my/path/v1/simple/val+01', $result->body['queryUrl']);
    }

    public function testSendTemplateArray()
    {
        $result = MockApiCalls::sendRequestWithParams(TemplateParam::init('tyu', ['val 01','**sad&?N','v4']));
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
        $result = MockApiCalls::sendRequestWithParams(TemplateParam::init('tyu', $mockObj));
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('my/path/v1/simple//val+01/v4///%2A%2Asad%26%3FN/v%5E%5E', $result->body['queryUrl']);
    }

    public function testSendSingleQuery()
    {
        $result = MockApiCalls::sendRequestWithParams(QueryParam::init('key', 'val 01'));
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray(explode('?', $result->body['queryUrl'])[1]);
        $this->assertEquals(['key' => 'val 01'], $query);
    }

    public function testSendSingleForm()
    {
        $result = MockApiCalls::sendRequestWithParams(
            FormParam::init('key', 'val 01'),
            HeaderParam::init('content-type', 'myContentTypeHeader')
        );
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertArrayNotHasKey('content-type', $result->body['headers']);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertEquals(['key' => 'val 01'], $query);
    }

    public function testSendFileForm()
    {
        $result = MockApiCalls::sendRequestWithParams(FormParam::init('myFile', MockHelper::getFileWrapper()));
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertStringEndsWith('testFile.txt', $query['myFile[name]']);
        $this->assertEquals('text/plain', $query['myFile[mime]']);
        $this->assertEquals('My Text', $query['myFile[postname]']);
    }

    public function testSendFileFormWithEncodingHeader()
    {
        $result = MockApiCalls::sendRequestWithParams(
            FormParam::init('myFile', MockHelper::getFileWrapper())->encodingHeader('content-type', 'image/png')
        );
        $this->assertInstanceOf(MockClass::class, $result);
        $query = self::convertQueryIntoArray($result->body['body']);
        $this->assertStringEndsWith('testFile.txt', $query['myFile[name]']);
        $this->assertEquals('text/plain', $query['myFile[mime]']);
        $this->assertEquals('My Text', $query['myFile[postname]']);
    }

    public function testSendMultipleQuery()
    {
        $result = MockApiCalls::sendRequestWithParams(
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
        );
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
            'keyE[body][2]' => '',
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
        $result = MockApiCalls::sendRequestWithParams(
            FormParam::init('key A', 'val 1'),
            FormParam::init('keyB', new MockClass([])),
            FormParam::init('keyB2', [2,4]),
            FormParam::init('keyB3', ['key1' => 2,'key2' => 4]),
            FormParam::init('keyC', new MockClass([23, 24,'asad'])),
            FormParam::init('keyD', new MockClass([23, 24]))->unIndexed(),
            FormParam::init('keyE', new MockClass([23, 24, new MockClass([1])]))->unIndexed(),
            FormParam::init('keyF', new MockClass([true, false, null]))->plain(),
            FormParam::init('keyG', new MockClass(['innerKey1' => 'A', 'innerKey2' => 'B']))->plain()
        );
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
            'keyF[body][2]' => '',
            'keyG[body][innerKey1]' => 'A',
            'keyG[body][innerKey2]' => 'B'
        ], $query);
    }

    public function testSendBodyParam()
    {
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(BodyParam::init('this is string'));

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::SCALAR, $result->body['headers']['content-type']);
        $this->assertEquals('this is string', $result->body['body']);
    }

    public function testSendBodyParamFile()
    {
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(BodyParam::init(MockHelper::getFileWrapper()));

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals('application/octet-stream', $result->body['headers']['content-type']);
        $this->assertEquals('"This test file is created to test CoreFileWrapper functionality"', $result->body['body']);
    }

    public function testSendMultipleBodyParams()
    {
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(
                BodyParam::init('this is string', 'key1'),
                BodyParam::init(new MockClass(['asad' => 'item1', 'ali' => 'item2']), 'key2')
            );

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::JSON, $result->body['headers']['content-type']);
        $this->assertEquals(
            '{"key1":"this is string","key2":{"body":{"asad":"item1","ali":"item2"}}}',
            $result->body['body']
        );
    }

    public function testSendXMLBodyParam()
    {
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(BodyParam::init('this is string'))
            ->bodyXml('myRoot');

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals(
            '<?xml version="1.0"?>' . "\n" . '<myRoot>this is string</myRoot>' . "\n",
            $result->body['body']
        );
    }

    public function testSendXMLNullBodyParam()
    {
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(BodyParam::init(null))
            ->bodyXml('myRoot');

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals('<?xml version="1.0"?>' . "\n", $result->body['body']);
    }

    public function testSendXMLArrayBodyParam()
    {
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(BodyParam::init(['this is string', 345, false, null]))
            ->bodyXmlArray('myRoot', 'innerItem');

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
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
        $requestBuilder = RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
            ->parameters(
                BodyParam::init('this is string', 'key1'),
                BodyParam::init('this is item 2', 'key2'),
                BodyParam::init(null, 'key3')
            )
            ->bodyXmlMap('bodyRoot');

        $result = MockApiCalls::sendRequestWithBodyParams($requestBuilder);
        $this->assertInstanceOf(MockClass::class, $result);
        $this->assertEquals(Format::XML, $result->body['headers']['content-type']);
        $this->assertEquals(
            '<?xml version="1.0"?>' . "\n" . '<bodyRoot><entry key="key1">this is string</entry>' .
            '<entry key="key2">this is item 2</entry></bodyRoot>' . "\n",
            $result->body['body']
        );
    }
}
