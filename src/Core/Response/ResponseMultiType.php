<?php

namespace CoreLib\Core\Response;

use Exception;

class ResponseMultiType
{
    /**
     * @var string|null
     */
    private $typeGroup;

    /**
     * @var string[]
     */
    private $deserializers = [];

    public function setTypeGroup(string $typeGroup): void
    {
        $this->typeGroup = $typeGroup;
    }

    public function setDeserializers(array $deserializers): void
    {
        $this->deserializers = $deserializers;
    }

    public function getFrom(Context $context, string $format)
    {
        if (is_null($this->typeGroup)) {
            return null;
        }
        $coreConfig = $context->getCoreConfig();
        $responseBody = $context->getResponse()->getBody();
        try {
            return $coreConfig->getJsonHelper()
                ->mapTypes($responseBody, $this->typeGroup, $this->deserializers);
        } catch (Exception $e) {
            throw $coreConfig->getConverter()
                ->createApiException($e->getMessage(), $context->getRequest(), $context->getResponse());
        }
    }
}
