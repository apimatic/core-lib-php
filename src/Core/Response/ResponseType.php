<?php

namespace CoreLib\Core\Response;

use Exception;

class ResponseType
{
    /**
     * @var string|null
     */
    private $responseClass;

    /**
     * @var int|null
     */
    private $dimensions;

    public function setResponseClass(string $responseClass): void
    {
        $this->responseClass = $responseClass;
    }

    public function setDimensions(int $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getFrom(Context $context, string $format)
    {
        if (is_null($this->responseClass)) {
            return null;
        }
        $coreConfig = $context->getCoreConfig();
        $responseBody = $context->getResponse()->getBody();
        try {
            return $coreConfig->getJsonHelper()
                ->mapClass($responseBody, $this->responseClass, $this->dimensions);
        } catch (Exception $e) {
            throw $coreConfig->getConverter()
                ->createApiException($e->getMessage(), $context->getRequest(), $context->getResponse());
        }
    }
}
