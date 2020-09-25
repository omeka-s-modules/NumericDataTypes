<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use NumericDataTypes\Form\Element\Integer as IntegerElement;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class Integer extends AbstractDataType
{
    /**
     * Minimum and maximum integers.
     *
     * Anything outside this range would exceed the safe minimum or maximum
     * range for JavaScript. Ideally we'd use the larger PHP_INT_MIN and
     * PHP_INT_MAX for the range, but since the data may be processed in the
     * browser (e.g. when decoding JSON and validating number inputs) we have to
     * settle on browser limitations.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MIN_SAFE_INTEGER
     */
    const MIN_SAFE_INT = -9007199254740991;
    const MAX_SAFE_INT = 9007199254740991;

    public function getName()
    {
        return 'numeric:integer';
    }

    public function getLabel()
    {
        return 'Integer'; // @translate
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return ['@value' => $value->value()];
        }
        return [
            '@value' => (int) $value->value(),
            '@type' => 'o-module-numeric-xsd:integer',
        ];
    }

    public function form(PhpRenderer $view)
    {
        $element = new IntegerElement('numeric-integer-value');
        $element->getValueElement()->setAttribute('data-value-key', '@value');
        return $view->formElement($element);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue($valueObject['@value']);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function isValid(array $valueObject)
    {
        return is_numeric($valueObject['@value'])
            && ((int) $valueObject['@value'] <= self::MAX_SAFE_INT)
            && ((int) $valueObject['@value'] >= self::MIN_SAFE_INT);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return $value->value();
        }
        return number_format($value->value());
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesInteger';
    }

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        $entity->setValue((int) $value->getValue());
    }

    /**
     * numeric => [
     *   int => [
     *     lt => [val => <integer>, pid => <propertyID>],
     *     gt => [val => <integer>, pid => <propertyID>],
     *   ],
     * ]
     */
    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
        if (isset($query['numeric']['int']['lt']['val'])
            && isset($query['numeric']['int']['lt']['pid'])
            && is_numeric($query['numeric']['int']['lt']['pid'])
        ) {
            $value = $query['numeric']['int']['lt']['val'];
            $propertyId = $query['numeric']['int']['lt']['pid'];
            if ($this->isValid(['@value' => $value])) {
                $number = (int) $value;
                $this->addLessThanQuery($adapter, $qb, $propertyId, $number);
            }
        }
        if (isset($query['numeric']['int']['gt']['val'])
            && isset($query['numeric']['int']['gt']['pid'])
            && is_numeric($query['numeric']['int']['gt']['pid'])
        ) {
            $value = $query['numeric']['int']['gt']['val'];
            $propertyId = $query['numeric']['int']['gt']['pid'];
            if ($this->isValid(['@value' => $value])) {
                $number = (int) $value;
                $this->addGreaterThanQuery($adapter, $qb, $propertyId, $number);
            }
        }
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
        if ('integer' === $type) {
            $alias = $adapter->createAlias();
            $qb->addSelect("MIN($alias.value) as HIDDEN numeric_value");
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", 'omeka_root.id'),
                    $qb->expr()->eq("$alias.property", $propertyId)
                )
            );
            $qb->addOrderBy('numeric_value', $query['sort_order']);
        }
    }
}
