<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\Request\RequestSetterInterface;

class BodyParam extends Parameter
{
    public static function init($value, ?string $key = null): self
    {
        return new self($key, $value);
    }

    private $bodyFormatSetter;
    private $bodyKey;

    private function __construct(?string $key, $value)
    {
        parent::__construct('', $value, 'body');
        $this->bodyKey = $key;
        $this->bodyFormatSetter = function (RequestSetterInterface $request): void {
            $request->setBodyAsJson();
        };
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

    public function xml(string $rootName): self
    {
        $this->bodyFormatSetter = function (RequestSetterInterface $request) use ($rootName): void {
            $request->setBodyAsXml($rootName);
        };
        return $this;
    }

    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addBodyParam($this->value, $this->bodyKey);
            ($this->bodyFormatSetter)($request);
        }
    }
}
