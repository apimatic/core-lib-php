<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\Request\RequestArraySerialization;
use CoreDesign\Core\Request\RequestSetterInterface;
use CoreLib\Types\Sdk\CoreFileWrapper;

class FormParam extends EncodedParam
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($key, $value);
        $instance->pickFromCollected($defaultValue);
        return $instance;
    }

    private const SUPPORTED_FORMATS = [
        RequestArraySerialization::INDEXED,
        RequestArraySerialization::UN_INDEXED,
        RequestArraySerialization::PLAIN
    ];

    /**
     * @var array<string,string>
     */
    private $encodingHeaders = [];
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

    public function encodingHeader(string $key, string $value): self
    {
        $this->encodingHeaders[$key] = $value;
        return $this;
    }

    public function format(string $format): self
    {
        if (in_array($format, self::SUPPORTED_FORMATS, true)) {
            $this->format = $format;
        }
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
        if ($this->value instanceof CoreFileWrapper) {
            if (isset($this->encodingHeaders['content-type'])) {
                $this->value = $this->value->createCurlFileInstance($this->encodingHeaders['content-type']);
            } else {
                $this->value = $this->value->createCurlFileInstance();
            }
            $request->addFormParam($this->key, $this->value);
            return;
        }
        $this->value = $this->prepareValue($this->value);
        if (!is_array($this->value)) {
            $request->addFormParam($this->key, $this->value);
            return;
        }
        $value = $this->httpBuildQuery($this->value, $this->format);
        if (empty($value)) {
            return;
        }
        $request->addFormParam($this->key, $value, $this->value);
    }
}
