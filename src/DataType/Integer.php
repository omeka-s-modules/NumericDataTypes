<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

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
    const MAX_SAFE_INT =  9007199254740991;

    public function getName()
    {
        return 'numeric:integer';
    }

    public function getLabel()
    {
        return 'Integer';
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return [
            '@value' => (int) $value->value(),
            '@type' => 'o-module-numeric-xsd:integer',
        ];
    }

    public function form(PhpRenderer $view)
    {
        $valueInput = new Element\Number('numeric-integer-value');
        $valueInput->setAttributes([
            'data-value-key' => '@value',
            'step' => 1,
            'min' => self::MIN_SAFE_INT,
            'max' => self::MAX_SAFE_INT,
        ]);
        return $view->formNumber($valueInput);
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
        return ($valueObject['@value'] <= self::MAX_SAFE_INT)
            && ($valueObject['@value'] >= self::MIN_SAFE_INT);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return number_format($value->value());
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesInteger';
    }

    /**
     * Get the integer from the value.
     *
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value)
    {
        return (int) $value;
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
            && is_numeric($query['numeric']['int']['lt']['val'])
            && is_numeric($query['numeric']['int']['lt']['pid'])
        ) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['int']['lt']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->lt(
                "$alias.value",
                $adapter->createNamedParameter($qb, $this->getNumberFromValue($query['numeric']['int']['lt']['val']))
            ));
        }
        if (isset($query['numeric']['int']['gt']['val'])
            && isset($query['numeric']['int']['gt']['pid'])
            && is_numeric($query['numeric']['int']['gt']['val'])
            && is_numeric($query['numeric']['int']['gt']['pid'])
        ) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['int']['gt']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->gt(
                "$alias.value",
                $adapter->createNamedParameter($qb, $this->getNumberFromValue($query['numeric']['int']['gt']['val']))
            ));
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
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", $propertyId)
                )
            );
            $qb->addOrderBy('numeric_value', $query['sort_order']);
        }
    }
}
