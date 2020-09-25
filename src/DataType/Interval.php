<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use NumericDataTypes\Form\Element\Interval as IntervalElement;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class Interval extends AbstractDateTimeDataType
{
    public function getName()
    {
        return 'numeric:interval';
    }

    public function getLabel()
    {
        return 'Interval'; // @translate
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return ['@value' => $value->value()];
    }

    public function form(PhpRenderer $view)
    {
        $element = new IntervalElement('numeric-interval-value');
        $element->getValueElement()->setAttribute('data-value-key', '@value');
        return $view->formElement($element);
    }

    /**
     * Is an interval value valid?
     *
     * Per the ISO 8601 specification, time intervals can be expressed in a few
     * ways, including <start>/<end>, <start>/<duration>, and <duration>/<end>,
     * but we only accept the <start>/<end> expression because it's easier for
     * users to convert a duration to a datetime than vice versa. The spec also
     * allows for concise representations of the end time point, but we do not
     * so we can reuse existing code.
     *
     * @param array $valueObject
     */
    public function isValid(array $valueObject)
    {
        $intervalPoints = explode('/', $valueObject['@value']);
        if (2 !== count($intervalPoints)) {
            // There must be a <start> point and an <end> point.
            return false;
        }
        try {
            $dateStart = $this->getDateTimeFromValue($intervalPoints[0]);
            $dateEnd = $this->getDateTimeFromValue($intervalPoints[1], false);
        } catch (\InvalidArgumentException $e) {
            // At least one point is invalid.
            return false;
        }
        $timestampStart = $dateStart['date']->getTimestamp();
        $timestampEnd = $dateEnd['date']->getTimestamp();
        if ($timestampStart >= $timestampEnd) {
            // The <start> point must be less than the <end> point.
            return false;
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        list($intervalStart, $intervalEnd) = explode('/', $valueObject['@value']);
        $dateStart = $this->getDateTimeFromValue($intervalStart);
        $dateEnd = $this->getDateTimeFromValue($intervalEnd, false);
        $interval = sprintf(
            '%s/%s',
            $dateStart['date']->format($dateStart['format_iso8601']),
            $dateEnd['date']->format($dateEnd['format_iso8601'])
        );
        $value->setValue($interval);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return $value->value();
        }
        list($intervalStart, $intervalEnd) = explode('/', $value->value());
        $dateStart = $this->getDateTimeFromValue($intervalStart);
        $dateEnd = $this->getDateTimeFromValue($intervalEnd, false);
        return sprintf(
            '%s – %s',
            $dateStart['date']->format($dateStart['format_render']),
            $dateEnd['date']->format($dateEnd['format_render'])
        );
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return sprintf('%s %s', $value->value(), $this->render($view, $value));
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesInterval';
    }

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        list($intervalStart, $intervalEnd) = explode('/', $value->getValue());
        $dateStart = $this->getDateTimeFromValue($intervalStart);
        $dateEnd = $this->getDateTimeFromValue($intervalEnd, false);
        $entity->setValue($dateStart['date']->getTimestamp());
        $entity->setValue2($dateEnd['date']->getTimestamp());
    }

    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
        if (isset($query['numeric']['ivl']['val'])
            && isset($query['numeric']['ivl']['pid'])
            && is_numeric($query['numeric']['ivl']['pid'])
        ) {
            $value = $query['numeric']['ivl']['val'];
            $propertyId = $query['numeric']['ivl']['pid'];
            try {
                $date = $this->getDateTimeFromValue($value);
                $number = $date['date']->getTimestamp();
            } catch (\InvalidArgumentException $e) {
                return; // invalid value
            }
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", 'omeka_root.id'),
                    $qb->expr()->eq("$alias.property", (int) $propertyId)
                )
            );
            $qb->andWhere($qb->expr()->lte(
                "$alias.value",
                $adapter->createNamedParameter($qb, $number)
            ));
            $qb->andWhere($qb->expr()->gte(
                "$alias.value2",
                $adapter->createNamedParameter($qb, $number)
            ));
        }
    }
}
