<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestArraySerialization;
use CoreInterfaces\Core\Request\RequestSetterInterface;

class QueryParam extends EncodedParam
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
        RequestArraySerialization::PLAIN,
        RequestArraySerialization::CSV,
        RequestArraySerialization::TSV,
        RequestArraySerialization::PSV
    ];

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'query');
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

    public function commaSeparated(): self
    {
        $this->format = RequestArraySerialization::CSV;
        return $this;
    }

    public function tabSeparated(): self
    {
        $this->format = RequestArraySerialization::TSV;
        return $this;
    }

    public function pipeSeparated(): self
    {
        $this->format = RequestArraySerialization::PSV;
        return $this;
    }

    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->validated) {
            return;
        }
        $value = $this->prepareValue($this->value);
        $query = $this->httpBuildQuery([$this->key => $value], $this->format);
        if (empty($query)) {
            return;
        }
        $hasParams = (strrpos($request->getQueryUrl(), '?') > 0);
        $separator = (($hasParams) ? '&' : '?');
        $request->appendPath($separator . $query);
    }
}
