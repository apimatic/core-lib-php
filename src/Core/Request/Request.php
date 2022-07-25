<?php

declare(strict_types=1);

namespace CoreLib\Core\Request;

use CoreDesign\Core\Request\RequestArraySerialization;
use CoreDesign\Core\Request\RequestSetterInterface;
use CoreDesign\Http\RetryOption;
use CoreDesign\Sdk\ConverterInterface;
use CoreLib\Utils\JsonHelper;

class Request implements RequestSetterInterface
{
    private $httpMethod;
    private $queryUrl;
    private $headers = [];
    private $parameters = [];
    private $body;
    private $retryOption = RetryOption::USE_GLOBAL_SETTINGS;

    /**
     * @param string $httpMethod
     * @param string $queryUrl
     */
    public function __construct(string $httpMethod, string $queryUrl)
    {
        $this->httpMethod = $httpMethod;
        $this->queryUrl = $queryUrl;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getQueryUrl(): string
    {
        return $this->queryUrl;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getRetryOption(): string
    {
        return $this->retryOption;
    }

    /**
     * Add or replace a single header
     *
     * @param string $key  key for the header
     * @param mixed $value value of the header
     */
    public function addHeader(string $key, $value): void
    {
        $this->headers[$key] = $value;
    }

    public function addTemplate(string $key, $value, bool $encode = true): void
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_null($value)) {
            $replaceValue = '';
        } elseif (is_array($value)) {
            $val = array_map('strval', $value);
            $val = $encode ? array_map('urlencode', $val) : $val;
            $replaceValue = implode("/", $val);
        } else {
            $val = strval($value);
            $replaceValue = $encode ? urlencode($val) : $val;
        }
        $this->queryUrl = str_replace("{{$key}}", $replaceValue, $this->queryUrl);
    }

    public function addQuery(string $key, $value, string $arrayFormat = RequestArraySerialization::INDEXED): void
    {
        $hasParams = (strrpos($this->queryUrl, '?') > 0);
        $this->queryUrl .= (($hasParams) ? '&' : '?');
        $this->queryUrl .= $this->httpBuildQuery([$key => $value], $arrayFormat);
    }

    /**
     * Add or replace a single form parameter
     *
     * @param string $key  key for the parameter
     * @param mixed $value value of the parameter
     */
    public function addFormParam(string $key, $value, string $arrayFormat = RequestArraySerialization::INDEXED): void
    {
        if (empty($this->parameters)) {
            $this->body = '';
        } else {
            $this->body .= '&';
        }
        $this->parameters[$key] = $value;
        $this->body .= $this->httpBuildQuery([$key => $value], $arrayFormat);
    }

    /**
     * Generate URL-encoded query string from the giving list of parameters.
     *
     * @param  array  $data   Input data to be encoded
     * @param  string $parent Parent name accessor
     *
     * @return string Url encoded query string
     */
    private function httpBuildQuery(array $data, string $format, string $parent = ''): string
    {
        if ($format == RequestArraySerialization::INDEXED) {
            return http_build_query($data);
        }
        $separatorFormat = in_array($format, [
            RequestArraySerialization::TSV,
            RequestArraySerialization::PSV,
            RequestArraySerialization::CSV
        ], true);
        $keyPostfix = ($format == RequestArraySerialization::UN_INDEXED) ? '[]' : '';
        $innerArray = !empty($parent);
        $innerAssociativeArray = $innerArray && JsonHelper::isAssociative($data);
        $first = true;
        $separator = substr($format, strpos($format, ':'));
        $r = [];
        foreach ($data as $k => $v) {
            if ($innerArray) {
                if (is_numeric($k) && is_scalar($v)) {
                    $k = $parent . $keyPostfix;
                } else {
                    $k = $parent . "[$k]";
                }
            }
            if (is_array($v)) {
                $r[] = static::httpBuildQuery($v, $format, $k);
                continue;
            }
            if ($separatorFormat) {
                if ($innerAssociativeArray || $first) {
                    $r[] = "&" . urlencode($k) . "=" . urlencode(strval($v));
                    $first = false;
                } else {
                    $r[] = urlencode($separator) . urlencode(strval($v));
                }
            } else {
                $r[] = urlencode($k) . "=" . urlencode(strval($v));
            }
        }
        return implode($separatorFormat ? '' : '&', $r);
    }

    public function addBodyParam(string $key, $value, bool $wrapInObject = true): void
    {
        $this->parameters = [];
        if (!$wrapInObject) {
            $this->body = $value;
            return;
        }
        if (is_array($this->body)) {
            $this->body[$key] = $value;
        } else {
            $this->body = [$key => $value];
        }
    }

    public function setRetryOption(string $retryOption): void
    {
        $this->retryOption = $retryOption;
    }

    public function convert(ConverterInterface $converter)
    {
        return $converter->createHttpRequest($this);
    }
}
