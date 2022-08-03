<?php

namespace CoreLib\Tests;

use CoreDesign\Http\RetryOption;
use CoreLib\Authentication\Auth;
use CoreLib\Core\ApiCall;
use CoreLib\Core\Request\Parameters\FormParam;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\QueryParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Core\Request\RequestBuilder;
use CoreLib\Core\Response\ErrorType;
use CoreLib\Core\Response\ResponseHandler;
use CoreLib\Core\TestCase\BodyMatchers\NativeBodyMatcher;
use CoreLib\Core\TestCase\CoreTestCase;
use CoreLib\Core\TestCase\TestParam;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Tests\Mocking\Other\MockException;
use CoreLib\Tests\Mocking\Other\MockException3;
use CoreLib\Utils\DateHelper;
use PHPUnit\Framework\TestCase;

class EndToEndTest extends TestCase
{
    public function newApiCall(): ApiCall
    {
        return new ApiCall(MockHelper::getCoreConfig());
    }

    public function globalResponseHandler(): ResponseHandler
    {
        return MockHelper::getCoreConfig()->getGlobalResponseHandler();
    }

    /**
     * @throws MockException
     */
    public function callEndpoint($template, $query, $header, $form1, $form2): MockClass
    {
        return $this->newApiCall()
            ->requestBuilder(RequestBuilder::init('POST', '/api/path/{sub-path}')
                ->server('Server2')
                ->parameters(
                    TemplateParam::init('sub-path', $template)->required()->strictType('oneof(string,int)'),
                    QueryParam::init('date array', $query)
                        ->required()
                        ->serializeBy([DateHelper::class, 'toRfc1123DateTimeArray'])
                        ->commaSeparated(),
                    HeaderParam::init('header', $header),
                    FormParam::init('form 1', $form1)
                        ->required()
                        ->encodingHeader('content-type', 'text/plain')
                        ->unIndexed(),
                    FormParam::init('form 2', $form2)->unIndexed()
                )
                ->auth(Auth::and('query', 'header'))
                ->retryOption(RetryOption::ENABLE_RETRY))
            ->responseHandler($this->globalResponseHandler()
                ->type(MockClass::class)
                ->throwErrorOn(405, ErrorType::init('Wrong payload 405', MockException3::class))
                ->nullOn404())
            ->execute();
    }

    private function newTestCase($result): CoreTestCase
    {
        return new CoreTestCase($this, MockHelper::getCallbackCatcher(), $result);
    }

    public function testEndpoint()
    {
        $template = TestParam::typeGroup('poster', 'oneof(string,int)');
        $query = TestParam::custom(
            '["Fri, 01 Oct 2021 00:00:00 GMT","Thu, 30 Sep 2021 00:00:00 GMT"]',
            [DateHelper::class, 'fromRfc1123DateTimeArray']
        );
        $header = 1234;
        $form1 = TestParam::file('https://raw.githubusercontent.com/apimatic/' .
            'core-lib-php/master/tests/Mocking/Other/testFile.txt');
        $form2 = TestParam::object('{"key1":"value 1","key2":false,"key3":2.3}');

        $result = null;
        try {
            $result = $this->callEndpoint($template, $query, $header, $form1, $form2);
        } catch (MockException $e) {
            var_dump($e->getMessage());
        }
        $this->newTestCase($result)
            ->expectStatusRange(200, 208)
            ->expectHeaders(['content-type' => 'application/json'])
            ->allowExtraHeaders()
            ->bodyMatcher(NativeBodyMatcher::init(TestParam::object('{"body":{"httpMethod":"POST","queryUrl":' .
                '"my\/path\/v2\/api\/path\/poster?&date+array=Fri%2C+01+Oct+2021+00%3A00%3A00+GMT%2CThu%2C+30+Sep' .
                '+2021+00%3A00%3A00+GMT&token=someAuthToken&authorization=accessToken","headers":{"additionalHead1"' .
                ':"headVal1","additionalHead2":"headVal2","header":1234,"token":"someAuthToken","authorization":' .
                '"accessToken","Accept":"application\/json"},"parameters":{"form 2":{"key1":"value 1","key2":"false"' .
                ',"key3":2.3}},"retryOption":"enableRetries"}}', MockClass::class), true))
            ->assert();
    }
}
