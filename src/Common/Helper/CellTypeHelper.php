<?php

namespace OpenSpout\Common\Helper;

/**
 * This class provides helper functions to determine the type of the cell value.
 */
class CellTypeHelper
{
    /**
     * @param null|mixed $value
     *
     * @return bool Whether the given value is considered "empty"
     */
    public static function isEmpty($value): bool
    {
        return null === $value || '' === $value;
    }

    /**
     * @param mixed $value
     *
     * @return bool Whether the given value is a non empty string
     */
    public static function isNonEmptyString($value): bool
    {
        return 'string' === \gettype($value) && '' !== $value;
    }

    /**
     * Returns whether the given value is numeric.
     * A numeric value is from type "integer" or "double" ("float" is not returned by gettype).
     *
     * @param mixed $value
     *
     * @return bool Whether the given value is numeric
     */
    public static function isNumeric($value): bool
    {
        $valueType = \gettype($value);

        return 'integer' === $valueType || 'double' === $valueType;
    }

    /**
     * Returns whether the given value is boolean.
     * "true"/"false" and 0/1 are not booleans.
     *
     * @param mixed $value
     *
     * @return bool Whether the given value is boolean
     */
    public static function isBoolean($value): bool
    {
        return 'boolean' === \gettype($value);
    }

    /**
     * Returns whether the given value is a DateTime or DateInterval object.
     *
     * @param mixed $value
     *
     * @return bool Whether the given value is a DateTime or DateInterval object
     */
    public static function isDateTimeOrDateInterval($value): bool
    {
        return
            $value instanceof \DateTimeInterface
            || $value instanceof \DateInterval
        ;
    }

    /**
     * @param mixed $value
     */
    public static function isFormula($value): bool
    {
        return \is_string($value) && isset($value[0]) && '=' === $value[0];
    }
}
