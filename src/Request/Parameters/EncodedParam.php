<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use Core\Utils\CoreHelper;
use CoreInterfaces\Core\Request\RequestArraySerialization;
use JsonSerializable;
use stdClass;

abstract class EncodedParam extends Parameter
{
    protected $format = RequestArraySerialization::INDEXED;
    protected function __construct(string $key, $value, string $typeName)
    {
        parent::__construct($key, $value, $typeName);
    }

    /**
     * Prepare a mixed typed value or array for form/query encoding.
     *
     * @param mixed $value  Any mixed typed value.
     *
     * @return mixed  A valid instance to be sent in form/query.
     */
    protected function prepareValue($value)
    {
        if (is_null($value)) {
            return null;
        } elseif (is_array($value)) {
            // recursively calling this function to resolve all types in any array
            return array_map([$this, 'prepareValue'], $value);
        } elseif (is_bool($value)) {
            return var_export($value, true);
        } elseif ($value instanceof JsonSerializable) {
            $modelArray = $value->jsonSerialize();
            // recursively calling this function to resolve all types in any model
            return array_map([$this, 'prepareValue'], $modelArray instanceof stdClass ? [] : $modelArray);
        }
        return $value;
    }

    /**
     * Generate URL-encoded query string from the giving list of parameters.
     *
     * @param  array  $data   Input data to be encoded
     * @param  string $parent Parent name accessor
     *
     * @return string Url encoded query string
     */
    protected function httpBuildQuery(array $data, string $format, string $parent = ''): string
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
        $innerAssociativeArray = $innerArray && CoreHelper::isAssociative($data);
        $first = true;
        $separator = substr($format, strpos($format, ':') + 1);
        $r = [];
        foreach ($data as $k => $v) {
            if (is_null($v)) {
                continue;
            }
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
                    $r[] = "&" . http_build_query([$k => $v]);
                    $first = false;
                } else {
                    $r[] = urlencode($separator) . urlencode(strval($v));
                }
            } else {
                $r[] = http_build_query([$k => $v]);
            }
        }
        return implode($separatorFormat ? '' : '&', $r);
    }
}
