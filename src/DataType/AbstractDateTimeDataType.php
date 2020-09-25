<?php
namespace NumericDataTypes\DataType;

use DateTime;
use DateTimeZone;

abstract class AbstractDateTimeDataType extends AbstractDataType
{
    /**
     * Minimum and maximum years.
     *
     * When converted to Unix timestamps, anything outside this range would
     * exceed the minimum or maximum range for a 64-bit integer.
     */
    const YEAR_MIN = -292277022656;
    const YEAR_MAX = 292277026595;

    /**
     * ISO 8601 datetime pattern
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
    const PATTERN_ISO8601 = '^(?<date>(?<year>-?\d{4,})(-(?<month>\d{2}))?(-(?<day>\d{2}))?)(?<time>(T(?<hour>\d{2}))?(:(?<minute>\d{2}))?(:(?<second>\d{2}))?)(?<offset>((?<offset_hour>[+-]\d{2})?(:(?<offset_minute>\d{2}))?)|Z?)$';

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

        // Match against ISO 8601, allowing for reduced accuracy.
        $isMatch = preg_match(sprintf('/%s/', self::PATTERN_ISO8601), $value, $matches);
        if (!$isMatch) {
            throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        $matches = array_filter($matches); // remove empty values
        // An hour requires a day.
        if (isset($matches['hour']) && !isset($matches['day'])) {
            throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        // An offset requires a time.
        if (isset($matches['offset']) && !isset($matches['time'])) {
            throw new \InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }

        // Set the datetime components included in the passed value.
        $dateTime = [
            'value' => $value,
            'date_value' => $matches['date'],
            'time_value' => isset($matches['time']) ? $matches['time'] : null,
            'offset_value' => isset($matches['offset']) ? $matches['offset'] : null,
            'year' => (int) $matches['year'],
            'month' => isset($matches['month']) ? (int) $matches['month'] : null,
            'day' => isset($matches['day']) ? (int) $matches['day'] : null,
            'hour' => isset($matches['hour']) ? (int) $matches['hour'] : null,
            'minute' => isset($matches['minute']) ? (int) $matches['minute'] : null,
            'second' => isset($matches['second']) ? (int) $matches['second'] : null,
            'offset_hour' => isset($matches['offset_hour']) ? (int) $matches['offset_hour'] : null,
            'offset_minute' => isset($matches['offset_minute']) ? (int) $matches['offset_minute'] : null,
        ];

        // Set the normalized datetime components. Each component not included
        // in the passed value is given a default value.
        $dateTime['month_normalized'] = isset($dateTime['month'])
            ? $dateTime['month'] : ($defaultFirst ? 1 : 12);
        // The last day takes special handling, as it depends on year/month.
        $dateTime['day_normalized'] = isset($dateTime['day'])
            ? $dateTime['day']
            : ($defaultFirst ? 1 : self::getLastDay($dateTime['year'], $dateTime['month_normalized']));
        $dateTime['hour_normalized'] = isset($dateTime['hour'])
            ? $dateTime['hour'] : ($defaultFirst ? 0 : 23);
        $dateTime['minute_normalized'] = isset($dateTime['minute'])
            ? $dateTime['minute'] : ($defaultFirst ? 0 : 59);
        $dateTime['second_normalized'] = isset($dateTime['second'])
            ? $dateTime['second'] : ($defaultFirst ? 0 : 59);
        $dateTime['offset_hour_normalized'] = isset($dateTime['offset_hour'])
            ? $dateTime['offset_hour'] : 0;
        $dateTime['offset_minute_normalized'] = isset($dateTime['offset_minute'])
            ? $dateTime['offset_minute'] : 0;
        // Set the UTC offset (+00:00) if no offset is provided.
        $dateTime['offset_normalized'] = isset($dateTime['offset_value'])
            ? ('Z' === $dateTime['offset_value'] ? '+00:00' : $dateTime['offset_value'])
            : '+00:00';

        // Validate ranges of the datetime component.
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
        if ((-23 > $dateTime['offset_hour_normalized']) || (23 < $dateTime['offset_hour_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid hour offset: %s', $dateTime['offset_hour_normalized']));
        }
        if ((0 > $dateTime['offset_minute_normalized']) || (59 < $dateTime['offset_minute_normalized'])) {
            throw new \InvalidArgumentException(sprintf('Invalid minute offset: %s', $dateTime['offset_minute_normalized']));
        }

        // Set the ISO 8601 format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset_value'])) {
            $format = 'Y-m-d\TH:i:sP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offset_value'])) {
            $format = 'Y-m-d\TH:iP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offset_value'])) {
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
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset_value'])) {
            $format = 'F j, Y H:i:s P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offset_value'])) {
            $format = 'F j, Y H:i P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offset_value'])) {
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
        $dateTime['date'] = new DateTime(null, new DateTimeZone($dateTime['offset_normalized']));
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
