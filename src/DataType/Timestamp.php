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
        $date = $this->getDateFromValue($value->value());
        if (isset($date['month']) && isset($date['day'])) {
            $dataType = 'date';
        } elseif (isset($date['month'])) {
            $dataType = 'gYearMonth';
        } else {
            $dataType = 'gYear';
        }
        return [
            '@value' => $value->value(),
            '@type' => sprintf('o-module-numeric-xsd:%s', $dataType),
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

        return sprintf(
            '%s%s%s%s',
            $view->formNumber($yearInput),
            $view->formSelect($monthSelect),
            $view->formNumber($dayInput),
            $view->formHidden($valueInput)
        );
    }

    public function isValid(array $valueObject)
    {
        try {
            $this->getDateFromValue($valueObject['@value']);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        // Normalize the date for value storage. The passed value may include
        // zero-padding on month and day. This removes zero-padding to ensure
        // consistent format.
        $date = $this->getDateFromValue($valueObject['@value']);
        if (isset($date['month']) && isset($date['day'])) {
            $dateFormat = 'Y-n-j';
        } elseif (isset($date['month'])) {
            $dateFormat = 'Y-n';
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
        $date = $this->getDateFromValue($value->value());
        if (isset($date['month']) && isset($date['day'])) {
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
        $date = $this->getDateFromValue($value);
        return $date['date']->getTimestamp();
    }

    /**
     * Get the decomposed date and DateTime object from the value.
     *
     * Also used to validate the date since validation is a side effect of
     * parsing the value into its component date pieces.
     *
     * At this granularity (yyyy-mm-dd) the date range is "-292277022656-12-31"
     * to "292277026596-12-4" which when converted to Unix timestamps reach
     * minimum and maximum 64-bit integers. However, for simplicity's sake, we
     * use the date range "-292277022656-12-31" to "292277026595-12-31".
     *
     * @param string $value
     * @return array|false Returns false if the date is invalid
     */
    public function getDateFromValue($value)
    {
        $isMatch = preg_match('/^(?<year>-?(\d+))(-(?<month>\d{1,2}))?(?:-(?<day>\d{1,2}))?$/', $value, $matches);
        if (!$isMatch) {
            throw new \InvalidArgumentException('Invalid date string');
        }
        $date = [
            'year' => (int) $matches['year'],
            'month' => isset($matches['month']) ? (int) $matches['month'] : null,
            'day' => isset($matches['day']) ? (int) $matches['day'] : null,
            'month_normalized' => isset($matches['month']) ? (int) $matches['month'] : 1,
            'day_normalized' => isset($matches['day']) ? (int) $matches['day'] : 1,
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
        // Adding the date object here to reduce code duplication.
        $date['date'] = new DateTime;
        $date['date']->setDate(
            $date['year'],
            $date['month_normalized'],
            $date['day_normalized']
        )->setTime(0, 0, 0);
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
            && is_numeric($query['numeric']['ts']['lt']['val'])
            && is_numeric($query['numeric']['ts']['lt']['pid'])
        ) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['ts']['lt']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->lt(
                "$alias.value",
                $adapter->createNamedParameter($qb, $this->getNumberFromValue($query['numeric']['ts']['lt']['val']))
            ));
        }
        if (isset($query['numeric']['ts']['gt']['val'])
            && isset($query['numeric']['ts']['gt']['pid'])
            && is_numeric($query['numeric']['ts']['gt']['val'])
            && is_numeric($query['numeric']['ts']['gt']['pid'])
        ) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['ts']['gt']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->gt(
                "$alias.value",
                $adapter->createNamedParameter($qb, $this->getNumberFromValue($query['numeric']['ts']['gt']['val']))
            ));
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
