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
     * Get the decomposed datetime and DateTime object from an ISO 8601 value.
     *
     * Also used to validate the datetime since validation is a side effect of
     * parsing the value into its component datetime pieces.
     *
     * @throws \InvalidArgumentException
     * @param string $value
     * @return array
     */
    public static function getDateTimeFromValue($value)
    {
        // Match against ISO 8601, allowing for reduced accuracy.
        $isMatch = preg_match('/^(?<year>-?\d{4,})(?:-(?<month>\d{2}))?(?:-(?<day>\d{2}))?(?:T(?<hour>\d{2}))?(?::(?<minute>\d{2}))?(?::(?<second>\d{2}))?$/', $value, $matches);
        if (!$isMatch) {
            throw new \InvalidArgumentException('Invalid datetime string, must use ISO 8601');
        }
        $date = [
            'year' => (int) $matches['year'],
            'month' => isset($matches['month']) ? (int) $matches['month'] : null,
            'day' => isset($matches['day']) ? (int) $matches['day'] : null,
            'hour' => isset($matches['hour']) ? (int) $matches['hour'] : null,
            'minute' => isset($matches['minute']) ? (int) $matches['minute'] : null,
            'second' => isset($matches['second']) ? (int) $matches['second'] : null,
            'month_normalized' => isset($matches['month']) ? (int) $matches['month'] : 1,
            'day_normalized' => isset($matches['day']) ? (int) $matches['day'] : 1,
            'hour_normalized' => isset($matches['hour']) ? (int) $matches['hour'] : 0,
            'minute_normalized' => isset($matches['minute']) ? (int) $matches['minute'] : 0,
            'second_normalized' => isset($matches['second']) ? (int) $matches['second'] : 0,
        ];
        if ((self::YEAR_MIN > $date['year']) || (self::YEAR_MAX < $date['year'])) {
            throw new \InvalidArgumentException('Invalid year');
        }
        if ((1 > $date['month_normalized']) || (12 < $date['month_normalized'])) {
            throw new \InvalidArgumentException('Invalid month');
        }
        if ((1 > $date['day_normalized']) || (31 < $date['day_normalized'])) {
            throw new \InvalidArgumentException('Invalid day');
        }
        if ((0 > $date['hour_normalized']) || (23 < $date['hour_normalized'])) {
            throw new \InvalidArgumentException('Invalid hour');
        }
        if ((0 > $date['minute_normalized']) || (59 < $date['minute_normalized'])) {
            throw new \InvalidArgumentException('Invalid minute');
        }
        if ((0 > $date['second_normalized']) || (59 < $date['second_normalized'])) {
            throw new \InvalidArgumentException('Invalid second');
        }

        // Set the ISO 8601 format.
        if (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second'])) {
            $format = sprintf('Y-m-d\TH:i:s', $date['minute'], $date['second']);
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute'])) {
            $format = sprintf('Y-m-d\TH:i', $date['minute']);
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour'])) {
            $format = 'Y-m-d\TH';
        } elseif (isset($date['month']) && isset($date['day'])) {
            $format = 'Y-m-d';
        } elseif (isset($date['month'])) {
            $format = 'Y-m';
        } else {
            $format = 'Y';
        }
        $date['format_iso8601'] = $format;

        // Set the render format.
        if (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second'])) {
            $format = 'F j, Y H:i:s';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute'])) {
            $format = 'F j, Y H:i';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour'])) {
            $format = 'F j, Y H';
        } elseif (isset($date['month']) && isset($date['day'])) {
            $format = 'F j, Y';
        } elseif (isset($date['month'])) {
            $format = 'F Y';
        } else {
            $format = 'Y';
        }
        $date['format_render'] = $format;

        // Adding the DateTime object here to reduce code duplication. To ensure
        // consistency, assume that the passed ISO 8601 value has already been
        // adjusted to Coordinated Universal Time (UTC). This avoids automatic
        // adjustments based on the server's default timezone.
        $date['date'] = new DateTime(null, new DateTimeZone('UTC'));
        $date['date']->setDate(
            $date['year'],
            $date['month_normalized'],
            $date['day_normalized']
        )->setTime(
            $date['hour_normalized'],
            $date['minute_normalized'],
            $date['second_normalized']
        );
        return $date;
    }

    public function getFormElementValue($name)
    {
        $valueInput = new Element\Hidden($name);
        $valueInput->setAttributes([
            'data-value-key' => '@value',
        ]);
        return $valueInput;
    }

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
