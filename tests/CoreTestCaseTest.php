<?php

namespace CoreLib\Tests;

use CoreLib\Core\CoreClient;
use CoreLib\Core\Request\Request;
use CoreLib\Core\Response\Context;
use CoreLib\Core\TestCase\BodyMatchers\KeysAndValuesBodyMatcher;
use CoreLib\Core\TestCase\BodyMatchers\KeysBodyMatcher;
use CoreLib\Core\TestCase\BodyMatchers\NativeBodyMatcher;
use CoreLib\Core\TestCase\BodyMatchers\RawBodyMatcher;
use CoreLib\Core\TestCase\CoreTestCase;
use CoreLib\Core\TestCase\TestParam;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Utils\DateHelper;
use PHPUnit\Framework\TestCase;

class CoreTestCaseTest extends TestCase
{
    /**
     * @var CoreClient
     */
    private static $coreClient;

    public static function setUpBeforeClass(): void
    {
        self::$coreClient = MockHelper::getCoreClient();
    }

    private static function getResponse(int $status, array $headers, $body): void
    {
        $response = new MockResponse();
        $response->setStatusCode($status);
        $response->setHeaders($headers);
        $response->setBody($body);
        $context = new Context(new Request('http://my/path'), $response, self::$coreClient);
        self::$coreClient->afterResponse($context);
    }

    private function newTestCase($result): CoreTestCase
    {
        return new CoreTestCase($this, MockHelper::getCallbackCatcher(), $result);
    }

    public function testScalarParam()
    {
        $param1 = 'This is string';

        self::getResponse(202, ['key1' => 'res/header', 'key2' => 'res/2nd'], $param1);

        $this->newTestCase($param1)
            ->expectStatus(202)
            ->expectHeaders(['key1' => ['res/header', true]])
            ->allowExtraHeaders()
            ->assert();

        $this->newTestCase($param1)
            ->expectStatusRange(200, 208)
            ->expectHeaders(['key1' => ['res/header', true], 'key2' => ['res/2nd', true]])
            ->bodyMatcher(RawBodyMatcher::init('This is string'))
            ->assert();

        $this->newTestCase($param1)
            ->expectStatusRange(200, 208)
            ->expectHeaders(['key1' => ['res/header', true], 'key2' => ['res/2nd', true]])
            ->bodyMatcher(NativeBodyMatcher::init('This is string'))
            ->assert();
    }

    public function testFileParam()
    {
        $file = TestParam::file('https://gist.githubusercontent.com/asadali214/0a64efec5353d351818475f928c50767/' .
            'raw/8ad3533799ecb4e01a753aaf04d248e6702d4947/testFile.txt');

        self::getResponse(200, [], $file);

        $this->newTestCase($file)
            ->expectStatus(200)
            ->bodyMatcher(RawBodyMatcher::init(TestParam::file('https://gist.githubusercontent.com/asadali214/' .
                '0a64efec5353d351818475f928c50767/raw/8ad3533799ecb4e01a753aaf04d248e6702d4947/testFile.txt')))
            ->assert();
    }

    public function testObjectParamForKeysAndValues()
    {
        $obj = TestParam::object('{"key1":"value 1","key2":false,"key3":2.3}');

        self::getResponse(200, [], $obj);

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(RawBodyMatcher::init('{"key1":"value 1","key2":false,"key3":2.3}'))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysAndValuesBodyMatcher::init(
                TestParam::object('{"key1":"value 1","key2":false,"key3":2.3}'),
                true,
                true
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysAndValuesBodyMatcher::init(
                TestParam::object('{"key1":"value 1","key2":false}'),
                true,
                false
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysAndValuesBodyMatcher::init(
                TestParam::object('{"key2":false,"key3":2.3,"key1":"value 1"}'),
                false,
                true
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysAndValuesBodyMatcher::init(
                TestParam::object('{"key2":false,"key3":2.3}'),
                false,
                false
            ))
            ->assert();
    }

    public function testObjectParamForKeys()
    {
        $obj = TestParam::object('{"key1":"value 1","key2":false,"key3":2.3}');

        self::getResponse(200, [], $obj);

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysBodyMatcher::init(
                TestParam::object('{"key1":"valueB","key2":true,"key3":"myString"}'),
                true,
                true
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysBodyMatcher::init(
                TestParam::object('{"key1":"value 1","key3":false}'),
                true,
                false
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysBodyMatcher::init(
                TestParam::object('{"key2":false,"key3":2.3,"key1":"value 1"}'),
                false,
                true
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(KeysBodyMatcher::init(
                TestParam::object('{"key2":{"key":"val"}}'),
                false,
                false
            ))
            ->assert();
    }

    public function testClassParamForNative()
    {
        $obj = TestParam::object('{"body":{"asad":"item1","ali":"item2"}}', MockClass::class);
        self::getResponse(200, [], $obj);
        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object('{"body":{"asad":"item1","ali":"item2"}}', MockClass::class)
            ))
            ->assert();

        $obj = TestParam::object('{"key1":{"body":{"asad":"item1","ali":"item2"}},' .
            '"key2":{"body":{"asad":"item1","ali":"item2"}}}', MockClass::class, 1);
        self::getResponse(200, [], $obj);
        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object('{"key1":{"body":{"asad":"item1","ali":"item2"}},' .
                    '"key2":{"body":{"asad":"item1","ali":"item2"}}}', MockClass::class, 1),
                true,
                true
            ))
            ->assert();
    }

    public function testDateParamForNative()
    {
        $obj = TestParam::custom('2021-10-01', [DateHelper::class, 'fromSimpleDate']);
        self::getResponse(200, [], $obj);
        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init($obj))
            ->assert();

        $obj = TestParam::custom(
            '{"key1":"2021-10-01","key2":"2021-10-02"}',
            [DateHelper::class, 'fromSimpleDateMap']
        );
        self::getResponse(200, [], $obj);
        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init($obj, true, true))
            ->assert();

        $obj = TestParam::custom('["2021-10-01","2021-10-02"]', [DateHelper::class, 'fromSimpleDateArray']);
        self::getResponse(200, [], $obj);
        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init($obj, true, true))
            ->assert();
    }

    public function testTypeGroupParamForNative()
    {
        $obj = TestParam::typeGroup('This is string', 'oneof(string,int)');
        self::getResponse(200, [], $obj);
        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::typeGroup('This is string', 'oneof(string,int)')
            ))
            ->assert();
    }

    public function testClassArrayParamForNative()
    {
        $obj = TestParam::object('[{"body":{"asad":"item1","ali":"item2"},"0":"other value"},' .
            '{"body":{"key1":"item1","key2":"item2","key3":"item3"}}]', MockClass::class, 1);
        self::getResponse(200, [], $obj);

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object('[{"0":"other value","body":{"ali":"item2","asad":"item1"}}' .
                    ',{"body":{"key1":"item1","key3":"item3","key2":"item2"}},{"body":{"key1":"item1"' .
                    ',"key3":"item3"}}]', MockClass::class, 1),
                false,
                false
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object('[{"body":{"asad":"item1","ali":"item2"},"0":"other value"},' .
                    '{"body":{"key1":"item1","key2":"item2","key3":"item3"}}]', MockClass::class, 1),
                true,
                true
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object(
                    '{"1":{"body":{"key2":"item2","key3":"item3"}}}',
                    MockClass::class,
                    1
                ),
                true,
                false
            ))
            ->assert();

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object('[{"0":"other value","body":{"ali":"item2","asad":"item1"}},' .
                    '{"body":{"key1":"item1","key3":"item3","key2":"item2"}}]', MockClass::class, 1),
                false,
                true
            ))
            ->assert();
    }

    public function testPrimitiveArrayParamForNative()
    {
        $obj = TestParam::object('["string1","string2"]');
        self::getResponse(200, [], $obj);

        $this->newTestCase($obj)
            ->expectStatus(200)
            ->bodyMatcher(NativeBodyMatcher::init(
                TestParam::object('["string1","string2","string10","string20"]'),
                false,
                false
            ))
            ->assert();
    }
}
