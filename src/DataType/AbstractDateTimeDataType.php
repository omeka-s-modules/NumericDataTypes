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
     * ISO 8601 datetime patterns
     *
     * The standard permits the expansion of the year representation beyond
     * 0000–9999, but only by prior agreement between the sender and the
     * receiver. Given that our year range is unusually large we shouldn't
     * require senders to zero-pad to 12 digits for every year. Users would have
     * to a) have prior knowledge of this unusual requirement, and b) convert
     * all existing ISO strings to accommodate it. This is needlessly
     * inconvenient and would be incompatible with most other systems. Instead,
     * we require the standard's zero-padding to 4 digits, but stray from the
     * standard by accepting non-zero padded integers beyond -9999 and 9999.
     *
     * Note that we only accept ISO 8601's extended format: the date segment
     * must include hyphens as separators, and the time and offset segments must
     * include colons as separators. This follows the standard's best practices,
     * which notes that "The basic format should be avoided in plain text."
     */
    const PATTERN_DATE = '^(?<year>-?\d{4,})(-(?<month>\d{2}))?(-(?<day>\d{2}))?$';
    const PATTERN_TIME = '^T(?<hour>\d{2})(:(?<minute>\d{2}))?(:(?<second>\d{2}))?$';
    const PATTERN_OFFSET = '^(?<offset_sign>[+-])(?<offset_hour>\d{2})(:(?<offset_minute>\d{2}))?$';

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

        // Before matching against ISO 8601, remove the trailing "Z" if it
        // exists. The Z timezone designator is redundant because we already use
        // Coordinated Universal Time (UTC) if no offset is provided.
        $value = rtrim($value, 'Z');

        // Match against ISO 8601, allowing for reduced accuracy.
        $dateMatches = [];
        $timeMatches = [];
        $offsetMatches = [];

        $dateTimeValues = preg_split('/(T)/', $value, null, PREG_SPLIT_DELIM_CAPTURE);
        if (3 < count($dateTimeValues)) {
            // More than one "T" found.
            throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        // Validate the ISO 8601 date segment.
        $dateIsMatch = preg_match(sprintf('/%s/', self::PATTERN_DATE), $dateTimeValues[0], $dateMatches);
        if (!$dateIsMatch) {
            throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        if (isset($dateTimeValues[2])) {
            // Validate an ISO 8601 time segment.
            if (!isset($dateMatches['year']) || !isset($dateMatches['month']) || !isset($dateMatches['day'])) {
                // The time segment requires a year, month, and day.
                throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
            }
            $timeOffsetValues = preg_split('/([+-])/', $dateTimeValues[2], null, PREG_SPLIT_DELIM_CAPTURE);
            if (3 < count($timeOffsetValues)) {
                // More than one "+" or "-" found.
                throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
            }
            $timeIsMatch = preg_match(
                sprintf('/%s/', self::PATTERN_TIME),
                $dateTimeValues[1] . $timeOffsetValues[0],
                $timeMatches
            );
            if (!$timeIsMatch) {
                throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
            }
            if (isset($timeOffsetValues[2])) {
                // Validate an ISO 8601 offset segment.
                if (!isset($timeMatches['hour'])) {
                    // The offset segment requires a year, month, day, and hour.
                    // Note that it does not require a minute or second.
                    throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
                }
                $offsetIsMatch = preg_match(
                    sprintf('/%s/', self::PATTERN_OFFSET),
                    $timeOffsetValues[1] . $timeOffsetValues[2],
                    $offsetMatches
                );
                if (!$offsetIsMatch) {
                    throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
                }
            }
        }

        // Set the datetime components included in the passed value.
        $dateTime = [
            'value' => $value,
            'dateValue' => $dateMatches[0],
            'timeValue' => isset($timeMatches[0]) ? $timeMatches[0] : null,
            'offsetValue' => isset($offsetMatches[0]) ? $offsetMatches[0] : null,
            'year' => (int) $dateMatches['year'],
            'month' => isset($dateMatches['month']) ? (int) $dateMatches['month'] : null,
            'day' => isset($dateMatches['day']) ? (int) $dateMatches['day'] : null,
            'hour' => isset($timeMatches['hour']) ? (int) $timeMatches['hour'] : null,
            'minute' => isset($timeMatches['minute']) ? (int) $timeMatches['minute'] : null,
            'second' => isset($timeMatches['second']) ? (int) $timeMatches['second'] : null,
            'offset_sign' => isset($offsetMatches['offset_sign']) ? $offsetMatches['offset_sign'] : null,
            'offset_hour' => isset($offsetMatches['offset_hour']) ? (int) $offsetMatches['offset_hour'] : null,
            'offset_minute' => isset($offsetMatches['offset_minute']) ? (int) $offsetMatches['offset_minute'] : null,
        ];

        // Set the normalized datetime components. Each component not included
        // in the passed value is given a default value.
        $dateTime['month_normalized'] = isset($dateTime['month'])
            ? $dateTime['month'] : ($defaultFirst ? 1 : 12); // default month
        // The last day takes special handling, as it depends on year/month.
        $dateTime['day_normalized'] = isset($dateTime['day'])
            ? $dateTime['day']
            : ($defaultFirst ? 1 : self::getLastDay($dateTime['year'], $dateTime['month_normalized'])); // default day
        $dateTime['hour_normalized'] = isset($dateTime['hour'])
            ? $dateTime['hour'] : ($defaultFirst ? 0 : 23); // default hour
        $dateTime['minute_normalized'] = isset($dateTime['minute'])
            ? $dateTime['minute'] : ($defaultFirst ? 0 : 59); // default minute
        $dateTime['second_normalized'] = isset($dateTime['second'])
            ? $dateTime['second'] : ($defaultFirst ? 0 : 59); // default second
        $dateTime['offset_hour_normalized'] = isset($dateTime['offset_hour'])
            ? $dateTime['offset_hour'] : 0; // default hour offset
        $dateTime['offset_minute_normalized'] = isset($dateTime['offset_minute'])
            ? $dateTime['offset_minute'] : 0; // default hour offset


        if ((self::YEAR_MIN > $dateTime['year']) || (self::YEAR_MAX < $dateTime['year'])) {
            throw new \InvalidArgumentException(sprintf('Invalid year: %s', $dateTime['year']));
        }
        if ((1 > $dateTime['month_normalized']) || (12 < $dateTime['month_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid month: %s', $dateTime['month_normalized']));
        }
        if ((1 > $dateTime['day_normalized']) || (31 < $dateTime['day_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid day: %s', $dateTime['day_normalized']));
        }
        if ((0 > $dateTime['hour_normalized']) || (23 < $dateTime['hour_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid hour: %s', $dateTime['hour_normalized']));
        }
        if ((0 > $dateTime['minute_normalized']) || (59 < $dateTime['minute_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid minute: %s', $dateTime['minute_normalized']));
        }
        if ((0 > $dateTime['second_normalized']) || (59 < $dateTime['second_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid second: %s', $dateTime['second_normalized']));
        }
        if ((0 > $dateTime['offset_hour_normalized']) || (23 < $dateTime['offset_hour_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid hour offset: %s', $dateTime['offset_hour_normalized']));
        }
        if ((0 > $dateTime['offset_minute_normalized']) || (59 < $dateTime['offset_minute_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid minute offset: %s', $dateTime['offset_minute_normalized']));
        }

        // Set the ISO 8601 format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offsetValue'])) {
            $format = 'Y-m-d\TH:i:sP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offsetValue'])) {
            $format = 'Y-m-d\TH:iP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offsetValue'])) {
            $format = 'Y-m-d\THP';
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
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offsetValue'])) {
            $format = 'F j, Y H:i:s P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offsetValue'])) {
            $format = 'F j, Y H:i P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offsetValue'])) {
            $format = 'F j, Y H P';
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
        $offset =  $dateTime['offsetValue'] ?: '+00:00';
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
