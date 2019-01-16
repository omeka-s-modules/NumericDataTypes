<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

class Interval extends AbstractDateTimeDataType
{
    public function getName()
    {
        return 'numeric:interval';
    }

    public function getLabel()
    {
        return 'Interval';
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return ['@value' => $value->value()];
    }

    public function form(PhpRenderer $view)
    {
        $html = <<<HTML
%s
<div class="numeric-datetime-inputs">
    <span>%s</span>
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
<div class="numeric-datetime-inputs">
    <span>%s</span>
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
            $view->formHidden($this->getFormElementValue('numeric-interval-value')),
            $view->translate('Start:'),
            $view->formNumber($this->getFormElementYear('numeric-interval-start-year')),
            $view->formSelect($this->getFormElementMonth('numeric-interval-start-month')),
            $view->formNumber($this->getFormElementDay('numeric-interval-start-day')),
            $view->translate('time'),
            $view->formNumber($this->getFormElementHour('numeric-interval-start-hour')),
            $view->formNumber($this->getFormElementMinute('numeric-interval-start-minute')),
            $view->formNumber($this->getFormElementSecond('numeric-interval-start-second')),
            $view->translate('End:'),
            $view->formNumber($this->getFormElementYear('numeric-interval-end-year')),
            $view->formSelect($this->getFormElementMonth('numeric-interval-end-month')),
            $view->formNumber($this->getFormElementDay('numeric-interval-end-day')),
            $view->translate('time'),
            $view->formNumber($this->getFormElementHour('numeric-interval-end-hour')),
            $view->formNumber($this->getFormElementMinute('numeric-interval-end-minute')),
            $view->formNumber($this->getFormElementSecond('numeric-interval-end-second'))
        );
    }

    public function isValid(array $valueObject)
    {
        $intervalPoints = explode('/', $valueObject['@value']);
        if (2 !== count($intervalPoints)) {
            return false;
        }
        foreach ($intervalPoints as $intervalPoint) {
            try {
                $this->getDateTimeFromValue($intervalPoint);
            } catch (\InvalidArgumentException $e) {
                return false;
            }
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        list($intervalStart, $intervalEnd) = explode('/', $valueObject['@value']);
        $dateStart = $this->getDateTimeFromValue($intervalStart);
        $dateEnd = $this->getDateTimeFromValue($intervalEnd, 'latest');
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
        list($intervalStart, $intervalEnd) = explode('/', $value->value());
        $dateStart = $this->getDateTimeFromValue($intervalStart);
        $dateEnd = $this->getDateTimeFromValue($intervalEnd, 'latest');
        return sprintf(
            '%s – %s',
            $dateStart['date']->format($dateStart['format_render']),
            $dateEnd['date']->format($dateEnd['format_render'])
        );
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesInterval';
    }

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        list($intervalStart, $intervalEnd) = explode('/', $value->getValue());
        $dateStart = $this->getDateTimeFromValue($intervalStart);
        $dateEnd = $this->getDateTimeFromValue($intervalEnd, 'latest');
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
            if ($this->isValid(['@value' => $value])) {
                $date = $this->getDateTimeFromValue($value);
                $number = $date['date']->getTimestamp();
                $alias = $adapter->createAlias();
                $qb->leftJoin(
                    $this->getEntityClass(), $alias, 'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
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
}
