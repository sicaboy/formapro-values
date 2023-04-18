<?php declare(strict_types=1);

namespace Formapro\Values\Cast;

class CastDateInterval
{
    public static function to(?\DateInterval $interval): ?array
    {
        if (null === $interval) {
            return null;
        }

        return [
            'interval' => $interval->format('P%yY%mM%dDT%HH%IM%SS'),
            'days' => $interval->days,
            'y' => $interval->y,
            'm' => $interval->m,
            'd' => $interval->d,
            'h' => $interval->h,
            'i' => $interval->i,
            's' => $interval->s,
        ];
    }

    public static function from($value): ?\DateInterval
    {
        if (null === $value) {
            return null;
        } elseif (is_array($value)) {
            return new \DateInterval($value['interval']);
        }

        return new \DateInterval($value);
    }
}
