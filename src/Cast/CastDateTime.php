<?php declare(strict_types=1);

namespace Formapro\Values\Cast;

use function Formapro\Values\get_value;
use function Formapro\Values\set_value;

class CastDateTime
{
    public static function set(object $object, string $key, ?\DateTime $value): void
    {
        set_value($object, $key, self::to($value));        
    }

    public static function get(object $object, string $key, \DateTime $default = null): ?\DateTime
    {
        return self::from(get_value($object, $key)) ?: $default;
    }

    public static function to(?\DateTime $date): ?array
    {
        if (null === $date) {
            return null;
        }

        return [
            'unix' => (int) $date->format('U'),
            'time' => (string) $date->format('Y-m-d\TH:i:s'),
            'tz' => $date->getTimezone()->getName(),
        ];
    }

    public static function from($value): ?\DateTime
    {
        if (null === $value) {
            return null;
        } elseif (is_numeric($value)) {
            return \DateTime::createFromFormat('U', $value);
        } elseif (is_array($value)) {
            if (isset($value['tz'])) {
                return \DateTime::createFromFormat('Y-m-d\TH:i:s', $value['time'], new \DateTimeZone($value['tz']));
            } else {
                // bc
                return \DateTime::createFromFormat('U', $value['unix']);
            }
        }

        return new \DateTime($value);
    }
}
