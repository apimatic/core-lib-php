<?php

declare(strict_types=1);

namespace Core\Utils;

use ArrayIterator;
use Core\Types\Sdk\CoreFileWrapper;
use InvalidArgumentException;

class CoreHelper
{
    /**
     * Serialize any given mixed value.
     *
     * @param mixed $value Any value to be serialized
     *
     * @return string|null serialized value
     */
    public static function serialize($value): ?string
    {
        if (is_string($value) || is_null($value)) {
            return $value;
        }
        if ($value instanceof CoreFileWrapper) {
            return $value->getFileContent();
        }
        return json_encode($value);
    }

    /**
     * Deserialize a Json string
     *
     * @param string|null $json A valid Json string
     *
     * @return mixed Decoded Json
     */
    public static function deserialize(?string $json, bool $associative = true)
    {
        return json_decode($json, $associative) ?? $json;
    }

    /**
     * Validates and processes the given Url to ensure safe usage with cURL.
     * @param string $url The given Url to process
     * @return string Pre-processed Url as string
     * @throws InvalidArgumentException
     */
    public static function validateUrl(string $url): string
    {
        //ensure that the urls are absolute
        $matchCount = preg_match("#^(https?://[^/]+)#", $url, $matches);
        if ($matchCount == 0) {
            throw new InvalidArgumentException('Invalid Url format.');
        }
        //get the http protocol match
        $protocol = $matches[1];

        //remove redundant forward slashes
        $query = substr($url, strlen($protocol));
        $query = preg_replace("#//+#", "/", $query);

        //return process url
        return $protocol . $query;
    }

    /**
     * Check if an array isAssociative (has string keys)
     *
     * @param  array $array Any value to be tested for associative array
     * @return boolean True if the array is Associative, false if it is Indexed
     */
    public static function isAssociative(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all the given value or values are present in the provided list.
     *
     * @param mixed $value        Value to be checked, could be scalar, array, 2D array, etc.
     * @param array $listOfValues List to be searched for values
     * @return bool Whether given value is present in the provided list
     */
    public static function checkValueOrValuesInList($value, array $listOfValues): bool
    {
        if (is_null($value)) {
            return true;
        }
        if (!is_array($value)) {
            return in_array($value, $listOfValues, true);
        }
        foreach ($value as $v) {
            if (!self::checkValueOrValuesInList($v, $listOfValues)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Clone the given value
     *
     * @param mixed $value Value to be cloned.
     * @return mixed Cloned value
     */
    public static function clone($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'clone'], $value);
        }
        if (is_object($value)) {
            return clone $value;
        }
        return $value;
    }

    /**
     * Recursively check whether the left value is a proper subset of the right value
     *
     * @param mixed $left          Left expected value
     * @param mixed $right         Right actual value
     * @param bool  $allowExtra    Are extra elements allowed in right array?
     * @param bool  $isOrdered     Should elements in right be compared in order to the left array?
     * @param bool  $checkValues   Check primitive values for equality?
     * @param bool  $nativeMatcher Should check arrays natively? i.e. allowExtra can be applied
     *                             on either $leftList or $rightList
     *
     * @return bool True if leftTree is a subset of rightTree
     */
    public static function isProperSubset(
        $left,
        $right,
        bool $allowExtra = true,
        bool $isOrdered = false,
        bool $checkValues = true,
        bool $nativeMatcher = false
    ): bool {
        $bothNull = self::checkForNull($left, $right);
        if (isset($bothNull)) {
            return !$checkValues || $bothNull;
        }
        $left = self::convertObjectToArray($left);
        $right = self::convertObjectToArray($right);
        $bothEqualPrimitive = self::checkForPrimitive($left, $right);
        if (isset($bothEqualPrimitive)) {
            return !$checkValues || $bothEqualPrimitive;
        }
        // Return false if size different and checking was strict
        if (!$allowExtra && count($left) != count($right)) {
            return false;
        }
        if (!CoreHelper::isAssociative($left)) {
            // If left array is indexed, right array should also be indexed
            if (CoreHelper::isAssociative($right)) {
                return !$checkValues;
            }
            if ($nativeMatcher && $allowExtra && count($left) > count($right)) {
                // Special IndexedArray case:
                // replacing left with right, as left array has more
                // elements and can not be proper subset of right array
                $tempLeft = $left;
                $left = $right;
                $right = $tempLeft;
            }
            return !$checkValues || self::isListProperSubsetOf($left, $right, $allowExtra, $isOrdered, $nativeMatcher);
        } else {
            // If left value is tree, right value should also be tree
            if (!CoreHelper::isAssociative($right)) {
                return !$checkValues;
            }
        }
        $keyNum = 0;
        for ($iterator = new ArrayIterator($left); $iterator->valid(); $iterator->next()) {
            $key = $iterator->key();
            $leftVal = $left[$key];
            // Check if key exists
            if (!array_key_exists($key, $right)) {
                return false;
            }
            if ($isOrdered) {
                $rightKeys = array_keys($right);
                // When $isOrdered, check if key exists at some next position
                if (!in_array($key, array_slice($rightKeys, $keyNum), true)) {
                    return false;
                }
                $keyNum = array_search($key, $rightKeys, true);
            }
            $rightVal = $right[$key];
            $keyNum += 1;
            if (!self::isProperSubset($leftVal, $rightVal, $allowExtra, $isOrdered, $checkValues, $nativeMatcher)) {
                return false;
            }
        }
        return true;
    }

    private static function checkForNull($left, $right): ?bool
    {
        if (is_null($left) && is_null($right)) {
            return true;
        }
        if (is_null($left) || is_null($right)) {
            return false;
        }
        return null;
    }

    private static function checkForPrimitive($left, $right): ?bool
    {
        if (!is_array($left) && !is_array($right)) {
            return $left === $right;
        }
        if (!is_array($left) || !is_array($right)) {
            return false;
        }
        return null;
    }

    /**
     * Check whether the list is a subset of another list.
     *
     * @param array $leftList      Expected left list
     * @param array $rightList     Right List to check
     * @param bool  $allowExtra    Are extras allowed in the right list to check?
     * @param bool  $isOrdered     Should checking be in order?
     * @param bool  $nativeMatcher Should check arrays natively? i.e. allowExtra can be applied
     *                             on either $leftList or $rightList
     *
     * @return bool True if $leftList is a subset of $rightList
     */
    private static function isListProperSubsetOf(
        array $leftList,
        array $rightList,
        bool $allowExtra = true,
        bool $isOrdered = false,
        bool $nativeMatcher = false
    ): bool {
        if ($isOrdered && !$allowExtra) {
            return $leftList === $rightList;
        } elseif ($isOrdered && $allowExtra) {
            return $leftList === array_slice($rightList, 0, count($leftList));
        } { // if (!$isOrdered && !$allowExtra) || (!$isOrdered && $allowExtra)
            return $leftList == self::intersectArrays($leftList, $rightList, $allowExtra, $isOrdered, $nativeMatcher);
        }
    }

    /**
     * Computes the intersection of arrays, even for arrays of arrays
     *
     * @param array $leftList  The array with main values to check
     * @param array $rightList An array to compare values against
     *
     * @return array An array containing all the values in the leftList
     *               which are also present in the rightList
     */
    private static function intersectArrays(
        array $leftList,
        array $rightList,
        bool $allowExtra = true,
        bool $isOrdered = false,
        bool $nativeMatcher = false
    ): array {
        $commonList = [];
        foreach ($leftList as $leftVal) {
            foreach ($rightList as $rightVal) {
                if (self::isProperSubset($leftVal, $rightVal, $allowExtra, $isOrdered, true, $nativeMatcher)) {
                    $commonList[] = $leftVal;
                    array_splice($rightList, array_search($rightVal, $rightList, true), 1);
                    break;
                }
            }
        }
        return $commonList;
    }

    private static function convertObjectToArray($object)
    {
        if (is_object($object)) {
            $object = (array) $object;
        }
        if (is_array($object)) {
            return array_map([self::class, 'convertObjectToArray'], $object);
        }
        return $object;
    }
}
