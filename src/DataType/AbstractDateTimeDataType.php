<?php
namespace NumericDataTypes\DataType;

use DateTime;
use DateTimeZone;
use Zend\Form\Element;

abstract class AbstractDateTimeDataType extends AbstractDataType
{
    /**
     * Minimum and maximum years.
     *
     * When converted to Unix timestamps, anything outside this range would
     * exceed the minimum or maximum range for a 64-bit integer.
     */
    const YEAR_MIN = -292277022656;
    const YEAR_MAX =  292277026595;

    /**
     * @var array Cache of date/times
     */
    protected static $dateTimes = [];

    /**
     * Get the decomposed date/time and DateTime object from an ISO 8601 value.
     *
     * Use $defaultFirst to set the default of each datetime component to its
     * first (true) or last (false) possible integer, if the specific component
     * is not passed with the value.
     *
     * Also used to validate the datetime since validation is a side effect of
     * parsing the value into its component datetime pieces.
     *
     * @throws \InvalidArgumentException
     * @param string $value
     * @param bool $defaultFirst
     * @return array
     */
    public static function getDateTimeFromValue($value, $defaultFirst = true)
    {
        if (isset(self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'])) {
            return self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'];
        }
        // Match against ISO 8601, allowing for reduced accuracy, but first
        // remove the trailing "Z" if it exists. The Z timezone designator is
        // redundant because we already use Coordinated Universal Time (UTC) if
        // no offset is provided.
        $value = rtrim($value, 'Z');
        $isMatch = preg_match('/^(?<year>-?\d{4,})(?:-(?<month>\d{2}))?(?:-(?<day>\d{2}))?(?:T(?<hour>\d{2}))?(?::(?<minute>\d{2}))?(?::(?<second>\d{2}))?(?<offset>(?<offset_sign>[+-])(?<offset_hour>\d{2}):(?<offset_minute>\d{2}))?$/', $value, $matches);
        if (!$isMatch) {
            throw new \InvalidArgumentException('Invalid datetime string, must use ISO 8601');
        }
        $dateTime = [
            'year' => (int) $matches['year'],
            'month' => isset($matches['month']) ? (int) $matches['month'] : null,
            'day' => isset($matches['day']) ? (int) $matches['day'] : null,
            'hour' => isset($matches['hour']) ? (int) $matches['hour'] : null,
            'minute' => isset($matches['minute']) ? (int) $matches['minute'] : null,
            'second' => isset($matches['second']) ? (int) $matches['second'] : null,
            'offset' => isset($matches['offset']) ? $matches['offset'] : null,
            'offset_sign' => isset($matches['offset_sign']) ? $matches['offset_sign'] : null,
            'offset_hour' => isset($matches['offset_hour']) ? $matches['offset_hour'] : null,
            'offset_minute' => isset($matches['offset_minute']) ? $matches['offset_minute'] : null,
            'month_normalized' => isset($matches['month'])
                ? (int) $matches['month']
                : ($defaultFirst ? 1 : 12), // default month
            'hour_normalized' => isset($matches['hour'])
                ? (int) $matches['hour']
                : ($defaultFirst ? 0 : 23), // default hour
            'minute_normalized' => isset($matches['minute'])
                ? (int) $matches['minute']
                : ($defaultFirst ? 0 : 59), // default minute
            'second_normalized' => isset($matches['second'])
                ? (int) $matches['second']
                : ($defaultFirst ? 0 : 59), // default second
            'offset_hour_normalized' => isset($matches['offset_hour'])
                ? (int) $matches['offset_hour']
                : 0, // default hour offset
            'offset_minute_normalized' => isset($matches['offset_minute'])
                ? (int) $matches['offset_minute']
                : 0, // default minute offset
        ];
        // The last day takes special handling, as it depends on year/month.
        $dateTime['day_normalized'] = isset($matches['day'])
            ? (int) $matches['day']
            : ($defaultFirst ? 1 : self::getLastDay($dateTime['year'], $dateTime['month_normalized'])); // default day

        if ((self::YEAR_MIN > $dateTime['year']) || (self::YEAR_MAX < $dateTime['year'])) {
            throw new \InvalidArgumentException('Invalid year');
        }
        if ((1 > $dateTime['month_normalized']) || (12 < $dateTime['month_normalized'])) {
            throw new \InvalidArgumentException('Invalid month');
        }
        if ((1 > $dateTime['day_normalized']) || (31 < $dateTime['day_normalized'])) {
            throw new \InvalidArgumentException('Invalid day');
        }
        if ((0 > $dateTime['hour_normalized']) || (23 < $dateTime['hour_normalized'])) {
            throw new \InvalidArgumentException('Invalid hour');
        }
        if ((0 > $dateTime['minute_normalized']) || (59 < $dateTime['minute_normalized'])) {
            throw new \InvalidArgumentException('Invalid minute');
        }
        if ((0 > $dateTime['second_normalized']) || (59 < $dateTime['second_normalized'])) {
            throw new \InvalidArgumentException('Invalid second');
        }
        if ((0 > $dateTime['offset_hour_normalized']) || (23 < $dateTime['offset_hour_normalized'])) {
            throw new \InvalidArgumentException('Invalid hour offset');
        }
        if ((0 > $dateTime['offset_minute_normalized']) || (59 < $dateTime['offset_minute_normalized'])) {
            throw new \InvalidArgumentException('Invalid miniute offset');
        }

        // Set the ISO 8601 format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset'])) {
            $format = 'Y-m-d\TH:i:sP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
            $format = 'Y-m-d\TH:i:s';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute'])) {
            $format = 'Y-m-d\TH:i';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour'])) {
            $format = 'Y-m-d\TH';
        } elseif (isset($dateTime['month']) && isset($dateTime['day'])) {
            $format = 'Y-m-d';
        } elseif (isset($dateTime['month'])) {
            $format = 'Y-m';
        } else {
            $format = 'Y';
        }
        $dateTime['format_iso8601'] = $format;

        // Set the render format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset'])) {
            $format = 'F j, Y H:i:s P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
            $format = 'F j, Y H:i:s';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute'])) {
            $format = 'F j, Y H:i';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour'])) {
            $format = 'F j, Y H';
        } elseif (isset($dateTime['month']) && isset($dateTime['day'])) {
            $format = 'F j, Y';
        } elseif (isset($dateTime['month'])) {
            $format = 'F Y';
        } else {
            $format = 'Y';
        }
        $dateTime['format_render'] = $format;

        // Adding the DateTime object here to reduce code duplication. To ensure
        // consistency, use Coordinated Universal Time (UTC) if no offset is
        // provided. This avoids automatic adjustments based on the server's
        // default timezone.
        $offset =  $dateTime['offset'] ?: '+00:00';
        $dateTime['date'] = new DateTime(null, new DateTimeZone($offset));
        $dateTime['date']->setDate(
            $dateTime['year'],
            $dateTime['month_normalized'],
            $dateTime['day_normalized']
        )->setTime(
            $dateTime['hour_normalized'],
            $dateTime['minute_normalized'],
            $dateTime['second_normalized']
        );
        self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'] = $dateTime; // Cache the date/time
        return $dateTime;
    }

    /**
     * Get the last day of a given year/month.
     *
     * @param int $year
     * @param int $month
     * @return int
     */
    public static function getLastDay($year, $month)
    {
        switch ($month) {
            case 2:
                // February (accounting for leap year)
                $leapYear = date('L', mktime(0, 0, 0, 1, 1, $year));
                return $leapYear ? 29 : 28;
            case 4:
            case 6:
            case 9:
            case 11:
                // April, June, September, November
                return 30;
            default:
                // January, March, May, July, August, October, December
                return 31;
        }
    }
}
