<?php

namespace CoreLib\Core\Response;

use Closure;
use Exception;

class ResponseType
{
    /**
     * @var string|null
     */
    private $responseClass;

    /**
     * @var callable|null
     */
    private $xmlDeserializer;

    /**
     * @var int|null
     */
    private $dimensions;

    public function setResponseClass(string $responseClass): void
    {
        $this->responseClass = $responseClass;
    }

    public function setXmlDeserializer(callable $xmlDeserializer): void
    {
        $this->xmlDeserializer = $xmlDeserializer;
    }

    public function setDimensions(int $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getFrom(Context $context)
    {
        if (is_null($this->responseClass)) {
            return null;
        }
        $coreConfig = $context->getCoreConfig();
        try {
            if (isset($this->xmlDeserializer)) {
                return Closure::fromCallable($this->xmlDeserializer)(
                    $context->getResponse()->getRawBody(),
                    $this->responseClass
                );
            }
            return $coreConfig->getJsonHelper()
                ->mapClass($context->getResponse()->getBody(), $this->responseClass, $this->dimensions);
        } catch (Exception $e) {
            throw $coreConfig->getConverter()
                ->createApiException($e->getMessage(), $context->getRequest(), $context->getResponse());
        }
    }
}
