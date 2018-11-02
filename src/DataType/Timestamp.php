<?php
namespace NumericDataTypes\DataType;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

class Timestamp extends AbstractDataType
{
    /**
     * Minimum and maximum years.
     *
     * When converted to Unix timestamps, anything outside this range would
     * exceed the minimum or maximum range for a 64-bit integer.
     */
    const YEAR_MIN = -292277022656;
    const YEAR_MAX =  292277026595;

    public function getName()
    {
        return 'numeric:timestamp';
    }

    public function getLabel()
    {
        return 'Timestamp';
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return [
            '@value' => $value->value(),
            '@type' => 'o-module-numeric-xsd:dateTime',
        ];
    }

    public function form(PhpRenderer $view)
    {
        $valueInput = new Element\Hidden('numeric-timestamp-value');
        $valueInput->setAttributes([
            'data-value-key' => '@value',
        ]);

        $yearInput = new Element\Number('numeric-timestamp-year');
        $yearInput->setAttributes([
            'step' => 1,
            'min' => self::YEAR_MIN,
            'max' => self::YEAR_MAX,
            'placeholder' => 'Enter year', // @translate
        ]);

        $monthSelect = new Element\Select('numeric-timestamp-month');
        $monthSelect->setEmptyOption('Select month'); // @translate
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

        $dayInput = new Element\Number('numeric-timestamp-day');
        $dayInput->setAttributes([
            'step' => 1,
            'min' => 1,
            'max' => 31,
            'placeholder' => 'Enter day', // @translate
        ]);

        $hourInput = new Element\Number('numeric-timestamp-hour');
        $hourInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'max' => 23,
            'placeholder' => 'Enter hour', // @translate
        ]);

        $minuteInput = new Element\Number('numeric-timestamp-minute');
        $minuteInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'max' => 59,
            'placeholder' => 'Enter minute', // @translate
        ]);

        $secondInput = new Element\Number('numeric-timestamp-second');
        $secondInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'max' => 59,
            'placeholder' => 'Enter second', // @translate
        ]);

        return $view->formNumber($yearInput)
            . $view->formSelect($monthSelect)
            . $view->formNumber($dayInput)
            . $view->formNumber($hourInput)
            . $view->formNumber($minuteInput)
            . $view->formNumber($secondInput)
            . $view->formHidden($valueInput);
    }

    public function isValid(array $valueObject)
    {
        try {
            $this->getDateTimeFromValue($valueObject['@value']);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        // Store the datetime in ISO 8601, allowing for reduced accuracy.
        $date = $this->getDateTimeFromValue($valueObject['@value']);
        if (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second'])) {
            $dateFormat = sprintf('Y-m-d\TH:i:s', $date['minute'], $date['second']);
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute'])) {
            $dateFormat = sprintf('Y-m-d\TH:i', $date['minute']);
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour'])) {
            $dateFormat = 'Y-m-d\TH';
        } elseif (isset($date['month']) && isset($date['day'])) {
            $dateFormat = 'Y-m-d';
        } elseif (isset($date['month'])) {
            $dateFormat = 'Y-m';
        } else {
            $dateFormat = 'Y';
        }
        $value->setValue($date['date']->format($dateFormat));
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        // Render the datetime, allowing for reduced accuracy.
        $date = $this->getDateTimeFromValue($value->value());
        if (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second'])) {
            $dateFormat = 'F j, Y H:i:s';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute'])) {
            $dateFormat = 'F j, Y H:i';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour'])) {
            $dateFormat = 'F j, Y H';
        } elseif (isset($date['month']) && isset($date['day'])) {
            $dateFormat = 'F j, Y';
        } elseif (isset($date['month'])) {
            $dateFormat = 'F Y';
        } else {
            $dateFormat = 'Y';
        }
        return $date['date']->format($dateFormat);
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesTimestamp';
    }

    /**
     * Get the Unix timestamp from the value.
     *
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value)
    {
        $date = $this->getDateTimeFromValue($value);
        return $date['date']->getTimestamp();
    }

    /**
     * Get the decomposed datetime and DateTime object from an ISO 8601 value.
     *
     * Also used to validate the datetime since validation is a side effect of
     * parsing the value into its component datetime pieces.
     *
     * @param string $value
     * @return array|false Returns false if the datetime is invalid
     */
    public static function getDateTimeFromValue($value)
    {
        $isMatch = preg_match('/^(?<year>-?(\d+))(-(?<month>\d{2}))?(?:-(?<day>\d{2}))?(?:T(?<hour>\d{2}))?(?::(?<minute>\d{2}))?(?::(?<second>\d{2}))?$/', $value, $matches);
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
        // Adding the date object here to reduce code duplication.
        $date['date'] = new DateTime;
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

    /**
     * numeric => [
     *   ts => [
     *     lt => [val => <date>, pid => <propertyID>],
     *     gt => [val => <date>, pid => <propertyID>],
     *   ],
     * ]
     */
    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
        if (isset($query['numeric']['ts']['lt']['val'])
            && isset($query['numeric']['ts']['lt']['pid'])
            && is_numeric($query['numeric']['ts']['lt']['pid'])
        ) {
            $value = $query['numeric']['ts']['lt']['val'];
            $propertyId = $query['numeric']['ts']['lt']['pid'];
            $this->addLessThanQuery($adapter, $qb, $propertyId, $value);
        }
        if (isset($query['numeric']['ts']['gt']['val'])
            && isset($query['numeric']['ts']['gt']['pid'])
            && is_numeric($query['numeric']['ts']['gt']['pid'])
        ) {
            $value = $query['numeric']['ts']['gt']['val'];
            $propertyId = $query['numeric']['ts']['gt']['pid'];
            $this->addGreaterThanQuery($adapter, $qb, $propertyId, $value);
        }
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
        if ('timestamp' === $type) {
            $alias = $adapter->createAlias();
            $qb->addSelect("MIN($alias.value) as HIDDEN numeric_value");
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", $propertyId)
                )
            );
            $qb->addOrderBy('numeric_value', $query['sort_order']);
        }
    }
}
