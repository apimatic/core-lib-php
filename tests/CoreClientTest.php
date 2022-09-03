<?php

namespace CoreLib\Tests;

use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreLib\Core\CoreClient;
use CoreLib\Core\Request\Parameters\BodyParam;
use CoreLib\Core\Request\Parameters\FormParam;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\QueryParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Core\Request\Request;
use CoreLib\Tests\Mocking\MockHelper;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CoreClientTest extends TestCase
{
    public function testHttpClient()
    {
        $httpClient = MockHelper::getCoreClient()->getHttpClient();
        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);

        $request = new Request('https://some/path');
        $response = $httpClient->execute($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['content-type' => 'application/json'], $response->getHeaders());
        $this->assertIsObject($response->getBody());
        $this->assertEquals(
            '{"body":{"httpMethod":"Get","queryUrl":"https:\/\/some\/path","headers":[],' .
            '"parameters":[],"parametersEncoded":[],"parametersMultipart":[],"body":null,' .
            '"retryOption":"useGlobalSettings"},"additionalProperties":[]}',
            $response->getRawBody()
        );
    }

    public function testApplyingParamsWithoutValidation()
    {
        $request = MockHelper::getCoreClient()->getGlobalRequest();
        $request->appendPath('/{newKey}');
        $queryUrl = $request->getQueryUrl();
        $headers = $request->getHeaders();
        $parameters = $request->getParameters();
        $body = $request->getBody();

        QueryParam::init('newKey', 'newVal')->apply($request);
        $this->assertEquals($request->getQueryUrl(), $queryUrl);

        TemplateParam::init('newKey', 'newVal')->apply($request);
        $this->assertEquals($request->getQueryUrl(), $queryUrl);

        HeaderParam::init('newKey', 'newVal')->apply($request);
        $this->assertEquals($request->getHeaders(), $headers);

        FormParam::init('newKey', 'newVal')->apply($request);
        $this->assertEquals($request->getParameters(), $parameters);

        BodyParam::init('newVal')->apply($request);
        $this->assertEquals($request->getBody(), $body);
    }

    /**
     * @throws Exception
     */
    public function fakeSerializeBy($argument)
    {
        throw new Exception('Invalid argument found');
    }

    public function testRequiredQueryParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required query field: newKey");

        QueryParam::init('newKey', null)->required()->validate(CoreClient::getJsonHelper(MockHelper::getCoreClient()));
    }

    public function testSerializeByQueryParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to serialize field: newKey, Due to:\nInvalid argument found");

        QueryParam::init('newKey', 'someVal')->serializeBy([$this, 'fakeSerializeBy'])->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testStrictTypeQueryParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to map Type: string on: oneof(int,bool)");

        QueryParam::init('newKey', 'someVal')->strictType('oneof(int,bool)')->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testRequiredTemplateParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required template field: newKey");

        TemplateParam::init('newKey', null)->required()->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testSerializeByTemplateParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to serialize field: newKey, Due to:\nInvalid argument found");

        TemplateParam::init('newKey', 'someVal')->serializeBy([$this, 'fakeSerializeBy'])->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testStrictTypeTemplateParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to map Type: string on: oneof(int,bool)");

        TemplateParam::init('newKey', 'someVal')->strictType('oneof(int,bool)')->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testRequiredFormParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required form field: newKey");

        FormParam::init('newKey', null)->required()->validate(CoreClient::getJsonHelper(MockHelper::getCoreClient()));
    }

    public function testSerializeByFormParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to serialize field: newKey, Due to:\nInvalid argument found");

        FormParam::init('newKey', 'someVal')->serializeBy([$this, 'fakeSerializeBy'])->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testStrictTypeFormParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to map Type: string on: oneof(int,bool)");

        FormParam::init('newKey', 'someVal')->strictType('oneof(int,bool)')->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testRequiredHeaderParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required header field: newKey");

        HeaderParam::init('newKey', null)->required()->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testSerializeByHeaderParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to serialize field: newKey, Due to:\nInvalid argument found");

        HeaderParam::init('newKey', 'someVal')->serializeBy([$this, 'fakeSerializeBy'])->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testStrictTypeHeaderParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to map Type: string on: oneof(int,bool)");

        HeaderParam::init('newKey', 'someVal')->strictType('oneof(int,bool)')->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testRequiredBodyParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required body field: body");

        BodyParam::init(null)->required()->validate(CoreClient::getJsonHelper(MockHelper::getCoreClient()));
    }

    public function testSerializeByBodyParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to serialize field: body, Due to:\nInvalid argument found");

        BodyParam::init('someVal')->serializeBy([$this, 'fakeSerializeBy'])->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }

    public function testStrictTypeBodyParamValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to map Type: string on: oneof(int,bool)");

        BodyParam::init('someVal')->strictType('oneof(int,bool)')->validate(
            CoreClient::getJsonHelper(MockHelper::getCoreClient())
        );
    }
}
