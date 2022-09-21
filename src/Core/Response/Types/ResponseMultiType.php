<?php

declare(strict_types=1);

namespace CoreLib\Core\Response\Types;

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
        $responseBody = $context->getResponse()->getBody();
        try {
            return $context->getJsonHelper()->mapTypes($responseBody, $this->typeGroup, $this->deserializers);
        } catch (Exception $e) {
            throw $context->toApiException($e->getMessage());
        }
    }
}
