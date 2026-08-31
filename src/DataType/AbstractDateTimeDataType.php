<?php
namespace NumericDataTypes\DataType;

use DateTime;
use DateTimeZone;
use IntlCalendar;
use IntlDateFormatter;
use IntlDatePatternGenerator;
use InvalidArgumentException;

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
     * Get relevant date/time information from an ISO 8601 value.
     *
     * Returns an array holding the value decomposed into its datetime
     * components, the format patterns used to render it, and a DateTime
     * object. Parsing doubles as validation, so an invalid value throws
     * rather than returning.
     *
     * Optional components are stored twice: the raw key is null when the value
     * omitted it, and the '_normalized' key is always set, filling omissions
     * per $defaultFirst. The year has no normalized counterpart, since a value
     * must include one. The DateTime is built from the normalized values, so
     * read the raw ones to see what was actually entered.
     *
     * $dateTime['year'] is the year as entered, which we assume follows the
     * historical convention: no year 0, so '-0099' means 99 BCE.
     * $dateTime['date'] is a DateTime on the astronomical year of the same
     * number, since the year is passed to DateTime::setDate() unchanged and
     * PHP's calendar counts a year 0, making it 100 BCE. The stored value
     * string keeps the entered year while indexed timestamps are built from
     * the DateTime, so the two persisted forms use different conventions.
     *
     * That one-year difference is consistent rather than correct. Searching
     * for '-0099' runs the search value through this same method, so it picks
     * up the same error and still matches the right items. Sorting survives
     * too: only BCE values shift, all by the same amount, and shifting them
     * earlier cannot reorder them against CE values. Code that computes a date
     * on its own and compares it to an indexed timestamp will be a year off.
     *
     * @throws InvalidArgumentException
     * @param string $value An ISO 8601 string, possibly of reduced accuracy
     * @param bool $defaultFirst Default omitted components to their first
     *     (true) or last (false) possible value. Pass false for the end of a
     *     range so it covers its whole period: '-0400' becomes 31 December
     *     23:59:59 of that year rather than its first instant.
     * @return array The decomposed datetime, its format patterns, and a DateTime
     */
    public static function getDateTimeFromValue($value, $defaultFirst = true)
    {
        if (isset(self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'])) {
            return self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'];
        }

        // Match against ISO 8601, allowing for reduced accuracy.
        $isMatch = preg_match(sprintf('/%s/', self::PATTERN_ISO8601), (string) $value, $matches);
        if (!$isMatch) {
            throw new InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        $matches = array_filter($matches); // remove empty values
        // An hour requires a day.
        if (isset($matches['hour']) && !isset($matches['day'])) {
            throw new InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        // An offset requires a time.
        if (isset($matches['offset']) && !isset($matches['time'])) {
            throw new InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }

        // Set the datetime components included in the passed value.
        $dateTime = [
            'value' => $value,
            'date_value' => $matches['date'],
            'time_value' => $matches['time'] ?? null,
            'offset_value' => $matches['offset'] ?? null,
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
        $dateTime['month_normalized'] = $dateTime['month'] ?? ($defaultFirst ? 1 : 12);
        // The last day takes special handling, as it depends on year/month.
        $dateTime['day_normalized'] = $dateTime['day']
            ?? ($defaultFirst ? 1 : self::getLastDay($dateTime['year'], $dateTime['month_normalized']));
        $dateTime['hour_normalized'] = $dateTime['hour'] ?? ($defaultFirst ? 0 : 23);
        $dateTime['minute_normalized'] = $dateTime['minute'] ?? ($defaultFirst ? 0 : 59);
        $dateTime['second_normalized'] = $dateTime['second'] ?? ($defaultFirst ? 0 : 59);
        $dateTime['offset_hour_normalized'] = $dateTime['offset_hour'] ?? 0;
        $dateTime['offset_minute_normalized'] = $dateTime['offset_minute'] ?? 0;
        // Set the UTC offset (+00:00) if no offset is provided.
        $dateTime['offset_normalized'] = isset($dateTime['offset_value'])
            ? ('Z' === $dateTime['offset_value'] ? '+00:00' : $dateTime['offset_value'])
            : '+00:00';

        // Validate ranges of the datetime component.
        if ((self::YEAR_MIN > $dateTime['year']) || (self::YEAR_MAX < $dateTime['year'])) {
            throw new InvalidArgumentException(sprintf('Invalid year: %s', $dateTime['year']));
        }
        if ((1 > $dateTime['month_normalized']) || (12 < $dateTime['month_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid month: %s', $dateTime['month_normalized']));
        }
        if ((1 > $dateTime['day_normalized']) || (31 < $dateTime['day_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid day: %s', $dateTime['day_normalized']));
        }
        if ((0 > $dateTime['hour_normalized']) || (23 < $dateTime['hour_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid hour: %s', $dateTime['hour_normalized']));
        }
        if ((0 > $dateTime['minute_normalized']) || (59 < $dateTime['minute_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid minute: %s', $dateTime['minute_normalized']));
        }
        if ((0 > $dateTime['second_normalized']) || (59 < $dateTime['second_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid second: %s', $dateTime['second_normalized']));
        }
        if ((-23 > $dateTime['offset_hour_normalized']) || (23 < $dateTime['offset_hour_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid hour offset: %s', $dateTime['offset_hour_normalized']));
        }
        if ((0 > $dateTime['offset_minute_normalized']) || (59 < $dateTime['offset_minute_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid minute offset: %s', $dateTime['offset_minute_normalized']));
        }

        // Set the ISO 8601 format and render format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset_value'])) {
            $formatIso8601 = 'Y-m-d\TH:i:sP';
            $formatRender = 'j F Y H:i:s P';
            $formatRenderIntl = 'd LLLL y G, HH:mm:ss xxx';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offset_value'])) {
            $formatIso8601 = 'Y-m-d\TH:iP';
            $formatRender = 'j F Y H:i P';
            $formatRenderIntl = 'd LLLL y G, HH:mm xxx';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offset_value'])) {
            $formatIso8601 = 'Y-m-d\THP';
            $formatRender = 'j F Y H P';
            $formatRenderIntl = 'd LLLL y G, HH xxx';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
            $formatIso8601 = 'Y-m-d\TH:i:s';
            $formatRender = 'j F Y H:i:s';
            $formatRenderIntl = 'd LLLL y G, HH:mm:ss';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute'])) {
            $formatIso8601 = 'Y-m-d\TH:i';
            $formatRender = 'j F Y H:i';
            $formatRenderIntl = 'd LLLL y G, HH:mm';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour'])) {
            $formatIso8601 = 'Y-m-d\TH';
            $formatRender = 'j F Y H';
            $formatRenderIntl = 'd LLLL y G, HH:mm';
        } elseif (isset($dateTime['month']) && isset($dateTime['day'])) {
            $formatIso8601 = 'Y-m-d';
            $formatRender = 'j F Y';
            $formatRenderIntl = 'd LLLL y G';
        } elseif (isset($dateTime['month'])) {
            $formatIso8601 = 'Y-m';
            $formatRender = 'F Y';
            $formatRenderIntl = 'LLLL y G';
        } else {
            $formatIso8601 = 'Y';
            $formatRender = 'Y';
            $formatRenderIntl = 'y G';
        }
        $dateTime['format_iso8601'] = $formatIso8601;
        $dateTime['format_render'] = $formatRender;
        $dateTime['format_render_intl'] = $formatRenderIntl;

        // Set the DateTime object.
        $dateTime['date'] = new DateTime('now', new DateTimeZone($dateTime['offset_normalized']));
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
     * Get a formatted (human-readable) date/time from an ISO 8601 value.
     *
     * Localizes the date/time with IntlDateFormatter and IntlCalendar. Falls
     * back to DateTime's own formatting when the intl extension is missing, or
     * when the year is outside the roughly +/-5.8M range IntlCalendar
     * supports, beyond which it silently wraps to an unrelated year.
     *
     * Year 0 renders as a bare "0" with no era. It is the one value the two
     * numberings disagree about: ISO 8601 reads 0000 as 1 BCE, while the
     * historical numbering we assume for entered years has no year 0. Showing
     * the astronomical year commits to neither and keeps it distinct from
     * -0001, which renders "1 BC" but indexes a year earlier.
     *
     * @see https://unicode-org.github.io/icu-docs/apidoc/dev/icu4j/com/ibm/icu/util/Calendar.html
     * @throws InvalidArgumentException
     * @param string $value An ISO 8601 string, possibly of reduced accuracy
     * @param bool $defaultFirst Default omitted components to their first
     *     (true) or last (false) possible value; see getDateTimeFromValue()
     * @param array $options Supports 'lang' to set the formatting locale
     * @return string The localized date/time, at the accuracy the value carried
     */
    public static function getFormattedDateTimeFromValue($value, $defaultFirst = true, $options = [])
    {
        $dateTime = self::getDateTimeFromValue($value, $defaultFirst);

        // Past this, IntlCalendar silently wraps to an unrelated year.
        $isOutsideBounds = ((5800000 < $dateTime['year']) || (-5800000 > $dateTime['year']));
        if (!extension_loaded('intl') || $isOutsideBounds) {
            return $dateTime['date']->format($dateTime['format_render']);
        }

        // Date and time types are NONE because setPattern() below supplies the
        // format. The offset becomes the timezone only when the value had one.
        $intlDateFormatter = new IntlDateFormatter(
            $options['lang'] ?? null,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $dateTime['offset_value'] ? sprintf('GMT%s', $dateTime['offset_normalized']) : null
        );
        // PHP 8.1 is required to use IntlDatePatternGenerator to get the best
        // date pattern for the given locale. Otherwise, use the default pattern.
        if (version_compare(phpversion(), '8.1', '>=')) {
            $intlDatePatternGenerator = new IntlDatePatternGenerator($options['lang'] ?? null);
            $format = $intlDatePatternGenerator->getBestPattern($dateTime['format_render_intl']);
        } else {
            $format = $dateTime['format_render_intl'];
        }
        if (0 < $dateTime['year']) {
            // No need for the era from year 1 on, since CE is implied. This
            // only strips a space-separated era token, so locales that place it
            // against the year (ja is 'Gy年') keep it.
            $format = str_replace([' G', 'G '], '', $format);
        } elseif (0 > $dateTime['year']) {
            // We assume users enter a negative year to match the historical
            // BCE year number, so that -5 reads as 5 BCE. This adjusted year
            // is passed to IntlCalendar::set() below, which numbers years
            // astronomically (extended year 0 is 1 BCE), so one is added to
            // line the two up.
            ++$dateTime['year'];
        } else {
            // The two numberings disagree about year 0: ISO 8601 reads 0000 as
            // 1 BCE, while the historical numbering we assume for entered years
            // has no year 0 at all. Labelling it "1 BC" would hide a real
            // difference, since 0000 and -0001 index a year apart, so render
            // the astronomical year instead ('u' rather than 'y G') for a bare
            // "0" that collides with nothing. The era is removed wherever it
            // sits, since some locales place it against the year (ja is 'Gy年')
            // where the strip above would not match it.
            $format = preg_replace('/\s*G+\s*/', '', $format);
            $format = str_replace('y', 'u', $format);
        }
        $intlDateFormatter->setPattern($format);

        $intlCalendar = IntlCalendar::createInstance(
            $dateTime['offset_value'] ? sprintf('GMT%s', $dateTime['offset_normalized']) : null
        );
        $intlCalendar->set(
            $dateTime['year'],
            $dateTime['month_normalized'] - 1, // IntlCalendar months are zero indexed
            $dateTime['day_normalized'],
            $dateTime['hour_normalized'],
            $dateTime['minute_normalized'],
            $dateTime['second_normalized']
        );

        return $intlDateFormatter->format($intlCalendar);
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
