<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\Request\RequestArraySerialization;
use CoreDesign\Core\Request\RequestSetterInterface;

class FormParam extends EncodedParam
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'form');
    }

    public function required(): self
    {
        parent::required();
        return $this;
    }

    public function serializeBy(callable $serializerMethod): self
    {
        parent::serializeBy($serializerMethod);
        return $this;
    }

    public function strictType(string $strictType, array $serializerMethods = []): self
    {
        parent::strictType($strictType, $serializerMethods);
        return $this;
    }

    public function unIndexed(): self
    {
        $this->format = RequestArraySerialization::UN_INDEXED;
        return $this;
    }

    public function plain(): self
    {
        $this->format = RequestArraySerialization::PLAIN;
        return $this;
    }

    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->validated) {
            return;
        }
        $value = $this->prepareValue($this->value);
        $request->addFormParam(
            $this->key,
            $value,
            $this->httpBuildQuery([$this->key => $value], $this->format)
        );
    }
}
