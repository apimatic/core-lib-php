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

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'query');
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
