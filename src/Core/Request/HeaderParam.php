<?php

declare(strict_types=1);

namespace CoreLib\Core\Request;

use CoreDesign\Core\Request\ParamInterface;
use CoreDesign\Core\Request\RequestInterface;

class HeaderParam implements ParamInterface
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var bool
     */
    private $valueMissing = false;

    private function __construct(string $key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function required(): self
    {
        if (is_null($this->value)) {
             $this->valueMissing = true;
        }
        return $this;
    }

    public function serializeBy(callable $serializerMethod): self
    {
        // TODO: Implement serializeBy() method.
        return $this;
    }

    public function typeGroup(string $typeGroup, array $serializerMethods = []): self
    {
        // TODO: Implement typeGroup() method.
        return $this;
    }

    public function apply(RequestInterface $request): void
    {
        if ($this->valueMissing) {
            throw new \InvalidArgumentException("Missing required field: $this->key");
        }
        $request->addHeader($this->key, $this->value);
    }
}
