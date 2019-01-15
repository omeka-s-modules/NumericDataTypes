<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\View\Renderer\PhpRenderer;

class Timestamp extends AbstractDateTimeDataType
{
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
        $date = $this->getDateTimeFromValue($value->value());
        $type = null;
        if (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#dateTime';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute'])) {
            $type = null; // XSD has no datatype for truncated seconds
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour'])) {
            $type = null; // XSD has no datatype for truncated minutes/seconds
        } elseif (isset($date['month']) && isset($date['day'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#date';
        } elseif (isset($date['month'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#gYearMonth';
        } else {
            $type = 'http://www.w3.org/2001/XMLSchema#gYear';
        }
        $jsonLd = ['@value' => $value->value()];
        if ($type) {
            $jsonLd['@type'] = $type;
        }
        return $jsonLd;
    }

    public function form(PhpRenderer $view)
    {
        $html = <<<HTML
%s
<div class="numeric-datetime-inputs">
    <div class="numeric-date-inputs">
        %s
        %s
        %s
        <a href="#" class="numeric-toggle-time">%s</a>
    </div>
    <div class="numeric-time-inputs">
        %s
        %s
        %s
    </div>
</div>
HTML;
        return sprintf(
            $html,
            $view->formHidden($this->getFormElementValue('numeric-timestamp-value')),
            $view->formNumber($this->getFormElementYear('numeric-timestamp-year')),
            $view->formSelect($this->getFormElementMonth('numeric-timestamp-month')),
            $view->formNumber($this->getFormElementDay('numeric-timestamp-day')),
            $view->translate('time'),
            $view->formNumber($this->getFormElementHour('numeric-timestamp-hour')),
            $view->formNumber($this->getFormElementMinute('numeric-timestamp-minute')),
            $view->formNumber($this->getFormElementSecond('numeric-timestamp-second'))
        );
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
        $value->setValue($date['date']->format($date['format_iso8601']));
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        // Render the datetime, allowing for reduced accuracy.
        $date = $this->getDateTimeFromValue($value->value());
        return $date['date']->format($date['format_render']);
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
