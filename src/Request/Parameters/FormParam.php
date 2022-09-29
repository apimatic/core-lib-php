<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use Core\Types\Sdk\CoreFileWrapper;
use CoreInterfaces\Core\Request\RequestArraySerialization;
use CoreInterfaces\Core\Request\RequestSetterInterface;

class FormParam extends EncodedParam
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    /**
     * @var array<string,string>
     */
    private $encodingHeaders = [];
    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'form');
    }

    public function encodingHeader(string $key, string $value): self
    {
        $this->encodingHeaders[strtolower($key)] = $value;
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
            $request->addMultipartFormParam($this->key, $this->value);
            return;
        }
        $this->value = $this->prepareValue($this->value);
        $encodedValue = $this->httpBuildQuery([$this->key => $this->value], $this->format);
        if (empty($encodedValue)) {
            return;
        }
        $request->addEncodedFormParam($this->key, $encodedValue, $this->value);
    }
}
