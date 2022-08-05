<?php

namespace CoreLib\Core\Response\Types;

use CoreLib\Core\CoreClient;
use CoreLib\Core\Response\Context;
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

    public function getFrom(Context $context)
    {
        if (is_null($this->typeGroup)) {
            return null;
        }
        $coreClient = $context->getCoreClient();
        $responseBody = $context->getResponse()->getBody();
        try {
            return CoreClient::getJsonHelper($context->getCoreClient())->mapTypes(
                $responseBody,
                $this->typeGroup,
                $this->deserializers
            );
        } catch (Exception $e) {
            throw CoreClient::getConverter($coreClient)
                ->createApiException($e->getMessage(), $context->getRequest(), $context->getResponse());
        }
    }
}
