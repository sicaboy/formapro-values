<?php declare(strict_types=1);

namespace Formapro\Values\Cast;

class CastDateTimeZone
{
    public static function to(?\DateTimeZone $zone): ?array
    {
        if (null === $zone) {
            return null;
        }

        return [
            'tz' => $zone->getName(),
        ];
    }

    public static function from($value): ?\DateTimeZone
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value) && array_key_exists('tz', $value)) {
            return new \DateTimeZone($value['tz']);
        }

        return new \DateTimeZone($value);
    }
}
