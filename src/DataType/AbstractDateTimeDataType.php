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
     * @param bool $first
     * @return array
     */
    public static function getDateTimeFromValue($value, $defaultFirst = true)
    {
        if (isset(self::$dateTimes[$value])) {
            return self::$dateTimes[$value];
        }
        // Match against ISO 8601, allowing for reduced accuracy.
        $isMatch = preg_match('/^(?<year>-?\d{4,})(?:-(?<month>\d{2}))?(?:-(?<day>\d{2}))?(?:T(?<hour>\d{2}))?(?::(?<minute>\d{2}))?(?::(?<second>\d{2}))?$/', $value, $matches);
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

        // Set the ISO 8601 format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
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
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
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
        // consistency, assume that the passed ISO 8601 value has already been
        // adjusted to Coordinated Universal Time (UTC). This avoids automatic
        // adjustments based on the server's default timezone.
        $dateTime['date'] = new DateTime(null, new DateTimeZone('UTC'));
        $dateTime['date']->setDate(
            $dateTime['year'],
            $dateTime['month_normalized'],
            $dateTime['day_normalized']
        )->setTime(
            $dateTime['hour_normalized'],
            $dateTime['minute_normalized'],
            $dateTime['second_normalized']
        );
        self::$dateTimes[$value] = $dateTime; // Cache the date/time
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

    /**
     * Get the value form element.
     *
     * @param string $name
     * @return Element\Hidden
     */
    public function getFormElementValue($name)
    {
        $valueInput = new Element\Hidden($name);
        $valueInput->setAttributes([
            'data-value-key' => '@value',
        ]);
        return $valueInput;
    }

    /**
     * Get the year form element.
     *
     * @param string $name
     * @return Element\Number
     */
    public function getFormElementYear($name)
    {
        $yearInput = new Element\Number($name);
        $yearInput->setAttributes([
            'step' => 1,
            'min' => self::YEAR_MIN,
            'max' => self::YEAR_MAX,
            'placeholder' => 'Year', // @translate
        ]);
        return $yearInput;
    }

    /**
     * Get the month form element.
     *
     * @param string $name
     * @return Element\Select
     */
    public function getFormElementMonth($name)
    {
        $monthSelect = new Element\Select($name);
        $monthSelect->setEmptyOption('Month'); // @translate
        $monthSelect->setValueOptions([
            1 => 'January', // @translate
            2 => 'February', // @translate
            3 => 'March', // @translate
            4 => 'April', // @translate
            5 => 'May', // @translate
            6 => 'June', // @translate
            7 => 'July', // @translate
            8 => 'August', // @translate
            9 => 'September', // @translate
            10 => 'October', // @translate
            11 => 'November', // @translate
            12 => 'December', // @translate
        ]);
        return $monthSelect;
    }

    /**
     * Get the day form element.
     *
     * @param string $name
     * @return Element\Number
     */
    public function getFormElementDay($name)
    {
        $dayInput = new Element\Number($name);
        $dayInput->setAttributes([
            'step' => 1,
            'min' => 1,
            'max' => 31,
            'placeholder' => 'Day', // @translate
        ]);
        return $dayInput;
    }

    /**
     * Get the hour form element.
     *
     * @param string $name
     * @return Element\Number
     */
    public function getFormElementHour($name)
    {
        $hourInput = new Element\Number($name);
        $hourInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'max' => 23,
            'placeholder' => 'Hour', // @translate
        ]);
        return $hourInput;
    }

    /**
     * Get the minute form element.
     *
     * @param string $name
     * @return Element\Number
     */
    public function getFormElementMinute($name)
    {
        $minuteInput = new Element\Number($name);
        $minuteInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'max' => 59,
            'placeholder' => 'Minute', // @translate
        ]);
        return $minuteInput;
    }

    /**
     * Get the second form element.
     *
     * @param string $name
     * @return Element\Number
     */
    public function getFormElementSecond($name)
    {
        $secondInput = new Element\Number($name);
        $secondInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'max' => 59,
            'placeholder' => 'Second', // @translate
        ]);
        return $secondInput;
    }
}
