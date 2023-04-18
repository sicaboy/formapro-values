<?php declare(strict_types=1);

namespace Formapro\Values\Cast;

class CastInt
{
    public static function to(?int $int): ?int
    {
        return $int;
    }

    public static function from($value): ?int
    {
        if (null !== $value) {
            settype($value, 'int');
        }

        return $value;
    }
}
