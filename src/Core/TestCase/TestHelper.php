<?php

namespace CoreLib\Core\TestCase;

use CoreLib\Utils\CoreHelper;

class TestHelper
{
    /**
     * Recursively check whether the left value is a proper subset of the right value
     *
     * @param mixed $left        Expected Left value
     * @param mixed $right       Right value
     * @param bool  $allowExtra  Are extra elements allowed in right array?
     * @param bool  $isOrdered   Should elements in right be compared in order to the left array?
     * @param bool  $checkValues Check primitive values for equality?
     *
     * @return bool True if leftTree is a subset of rightTree
     */
    public static function isProperSubsetOf(
        $left,
        $right,
        bool $allowExtra = true,
        bool $isOrdered = false,
        bool $checkValues = true
    ): bool {
        if ($left === null) {
            return true;
        }

        if ($right === null) {
            return false;
        }

        // If both values are primitive, check if they are equal
        if (!is_array($left) && !is_array($right)) {
            return $left === $right;
        }

        // Check if one of the values is primitive and the other is not
        if (!is_array($left) || !is_array($right)) {
            return false;
        }

        for ($iterator = new \ArrayIterator($left); $iterator->valid(); $iterator->next()) {
            $key = $iterator->key();
            $leftVal = $left[$key];
            $rightVal = $right[$key];

            // Check if key exists
            if (!array_key_exists($key, $right)) {
                return false;
            }

            if (CoreHelper::isAssociative($leftVal)) {
                // If left value is tree, right value should be be tree too
                if (CoreHelper::isAssociative($rightVal)) {
                    if (
                        !static::isProperSubsetOf(
                            $leftVal,
                            $rightVal,
                            $checkValues,
                            $allowExtra,
                            $isOrdered
                        )
                    ) {
                        return false;
                    }
                } else {
                    return false;
                }
            } elseif ($checkValues) {
                if (is_array($leftVal)) {
                    if (!is_array($rightVal)) {
                        return false;
                    }
                    if (count($leftVal) > 0 && CoreHelper::isAssociative($leftVal[0])) {
                        if (
                            !static::isArrayOfJsonObjectsProperSubsetOf(
                                $leftVal,
                                $rightVal,
                                $allowExtra,
                                $isOrdered,
                                $checkValues
                            )
                        ) {
                            return false;
                        }
                    } else {
                        if (
                            !static::isListProperSubsetOf(
                                $leftVal,
                                $rightVal,
                                $allowExtra,
                                $isOrdered
                            )
                        ) {
                            return false;
                        }
                    }
                } elseif (
                    !static::isProperSubsetOf(
                        $leftVal,
                        $rightVal,
                        $checkValues,
                        $allowExtra,
                        $isOrdered
                    )
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if left array of objects is a subset of right array.
     *
     * @param array<string,mixed> $left        Expected Left array
     * @param array<string,mixed> $right       Right array
     * @param bool                $allowExtra  Are extra elements allowed in right array?
     * @param bool                $isOrdered   Should elements in right array be compared in order to the left array?
     * @param bool                $checkValues Check primitive values for equality?
     *
     * @return bool True if $left array is a subset of $right array
     */
    public static function isArrayOfJsonObjectsProperSubsetOf(
        array $left,
        array $right,
        bool $allowExtra = true,
        bool $isOrdered = false,
        bool $checkValues = true
    ): bool {

        // Return false if size different and checking was strict
        if (!$allowExtra && count($left) != count($right)) {
            return false;
        }

        // Create list iterators
        $leftIter = (new \ArrayObject($left))->getIterator();
        $rightIter = (new \ArrayObject($right))->getIterator();

        // Iterate left list and check if each value is present in the right list
        while ($leftIter->valid()) {
            $leftIter->next();
            $leftTree = $leftIter->current();
            $found = false;

            // If order is not required, then search right array from beginning
            if (!$isOrdered) {
                $rightIter->rewind();
            }

            // Check each right element to see if left is a subset
            while ($rightIter->valid()) {
                $rightIter->next();
                if (
                    static::isProperSubsetOf(
                        $leftTree,
                        $rightIter->current(),
                        $checkValues,
                        $allowExtra,
                        $isOrdered
                    )
                ) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether the list is a subset of another list.
     *
     * @param array $leftList   Expected left list
     * @param array $rightList  Right List to check
     * @param bool  $allowExtra Are extras allowed in the right list to check?
     * @param bool  $isOrdered  Should checking be in order?
     *
     * @return bool True if $leftList is a subset of $rightList
     */
    public static function isListProperSubsetOf(
        array $leftList,
        array $rightList,
        bool $allowExtra = true,
        bool $isOrdered = false
    ): bool {
        if ($isOrdered && !$allowExtra) {
            return $leftList === $rightList;
        } elseif ($isOrdered && $allowExtra) {
            return array_slice($rightList, 0, count($leftList)) === $leftList;
        } elseif (!$isOrdered && !$allowExtra) {
            return count($leftList) == count($rightList) && self::intersectArrays($leftList, $rightList) == $leftList;
        } elseif (!$isOrdered && $allowExtra) {
            return self::intersectArrays($leftList, $rightList) == $leftList;
        }
        return true;
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
    public static function intersectArrays(array $leftList, array $rightList): array
    {
        return array_map(
            function ($param) {
                return CoreHelper::deserialize($param);
            },
            array_intersect(
                array_map([CoreHelper::class, 'serialize'], $leftList),
                array_map([CoreHelper::class, 'serialize'], $rightList)
            )
        );
    }
}
