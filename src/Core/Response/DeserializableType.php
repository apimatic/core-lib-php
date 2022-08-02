<?php

namespace CoreLib\Core\Response;

use Closure;
use CoreLib\Core\CoreConfig;
use Throwable;

class DeserializableType
{
    /**
     * @var callable|null
     */
    private $deserializerMethod;

    public function setDeserializerMethod(callable $deserializerMethod): void
    {
        $this->deserializerMethod = $deserializerMethod;
    }

    public function getFrom(Context $context)
    {
        if (is_null($this->deserializerMethod)) {
            return null;
        }
        try {
            return Closure::fromCallable($this->deserializerMethod)($context->getResponse()->getBody());
        } catch (Throwable $t) {
            throw CoreConfig::getConverter($context->getCoreConfig())
                ->createApiException($t->getMessage(), $context->getRequest(), $context->getResponse());
        }
    }
}
