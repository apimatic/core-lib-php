<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use Core\Types\Sdk\CoreFileWrapper;
use Core\Utils\CoreHelper;
use CoreInterfaces\Core\Request\RequestArraySerialization;
use CoreInterfaces\Core\Request\RequestSetterInterface;
use CURLFile;

class FormParam extends EncodedParam
{
    /**
     * Initializes a form parameter with the key and value provided.
     */
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

    /**
     * Sets encoding header with the key and value provided.
     *
     * @param string $key
     * @param string $value
     */
    public function encodingHeader(string $key, string $value): self
    {
        if (strtolower($key) == 'content-type') {
            $key = 'Content-Type';
        }
        $this->encodingHeaders[$key] = $value;
        return $this;
    }

    /**
     * Sets the parameter format to un-indexed.
     */
    public function unIndexed(): self
    {
        $this->format = RequestArraySerialization::UN_INDEXED;
        return $this;
    }

    /**
     * Sets the parameter format to plain.
     */
    public function plain(): self
    {
        $this->format = RequestArraySerialization::PLAIN;
        return $this;
    }

    private function isMultipart(): bool
    {
        if ($this->value instanceof CoreFileWrapper) {
            return true;
        }
        return isset($this->encodingHeaders['Content-Type']) &&
            $this->encodingHeaders['Content-Type'] != 'application/x-www-form-urlencoded';
    }

    /**
     * Returns multipart data.
     *
     * @return CURLFile|array CURLFile for FileWrapper value and wrapped data with encodingHeaders
     *                        as array for all other types of value
     */
    private function getMultipartData()
    {
        if ($this->value instanceof CoreFileWrapper) {
            if (isset($this->encodingHeaders['Content-Type'])) {
                return $this->value->createCurlFileInstance($this->encodingHeaders['Content-Type']);
            }
            return $this->value->createCurlFileInstance();
        }
        return [
            'data' => CoreHelper::serialize($this->prepareValue($this->value)),
            'headers' => $this->encodingHeaders
        ];
    }

    /**
     * Adds the parameter to the request provided.
     *
     * @param RequestSetterInterface $request The request to add the parameter to.
     */
    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->validated) {
            return;
        }
        if ($this->isMultipart()) {
            $request->addMultipartFormParam($this->key, $this->getMultipartData());
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
