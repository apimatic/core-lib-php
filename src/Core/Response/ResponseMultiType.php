<?php

namespace CoreLib\Core\Response;

use CoreLib\Core\CoreConfig;
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
        $coreConfig = $context->getCoreConfig();
        $responseBody = $context->getResponse()->getBody();
        try {
            return CoreConfig::getJsonHelper($context->getCoreConfig())->mapTypes(
                $responseBody,
                $this->typeGroup,
                $this->deserializers
            );
        } catch (Exception $e) {
            throw CoreConfig::getConverter($coreConfig)
                ->createApiException($e->getMessage(), $context->getRequest(), $context->getResponse());
        }
    }
}
