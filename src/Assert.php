<?php

declare(strict_types=1);

namespace Formapro\Values;

/**
 * @internal
 */
class Assert
{
    public static function nullClassOrClosure($value): void
    {
        if (null === $value || is_callable($value) || class_exists($value)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'Expected null, callable, or class. Got %s',
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }

    public static function propertyExists(object $object, string $property): void
    {
        if (false == property_exists($object, $property)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected the property %s::%s to exist',
                get_class($object),
                $property
            ));
        }
    }

    public static function isArray($value): void
    {
        if (false == is_array($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected array. Got %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function scalarOrArrayOfScalars($value): void
    {
        self::doScalarOrArrayOfScalars($value, '');
    }

    private static function doScalarOrArrayOfScalars($value, $key): void
    {
        if (is_scalar($value) || is_null($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                self::doScalarOrArrayOfScalars($v, $key ? "$key.$k" : $k);
            }

            return;
        }

        if ($key) {
            throw new \InvalidArgumentException(sprintf(
                'Expected array or scalar. Got %s at path %s',
                is_object($value) ? get_class($value) : gettype($value),
                $key
            ));
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Expected array or scalar. Got %s',
                is_object($value) ? get_class($value) : gettype($value),
                $key
            ));
        }

    }

    private function __construct()
    {
    }
}