<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\BodyFormat;
use CoreDesign\Core\Request\RequestSetterInterface;

class BodyParam extends Parameter
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    private $format = BodyFormat::JSON;
    private $wrapInObject = false;
    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'body');
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

    public function typeGroup(string $typeGroup, array $serializerMethods = []): self
    {
        parent::typeGroup($typeGroup, $serializerMethods);
        return $this;
    }

    public function wrapInObject(): self
    {
        $this->wrapInObject = true;
        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function apply(RequestSetterInterface $request): void
    {
        parent::validate();
        $request->addBodyParam($this->key, $this->value, $this->wrapInObject, $this->format);
    }
}
