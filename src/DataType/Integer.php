<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use NumericDataTypes\Form\Element\Integer as IntegerElement;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Omeka\DataType\ValueAnnotatingInterface;
use Laminas\View\Renderer\PhpRenderer;

class Integer extends AbstractDataType implements ValueAnnotatingInterface
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
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MAX_SAFE_INTEGER
     */
    const MIN_SAFE_INT = -9007199254740991;
    const MAX_SAFE_INT = 9007199254740991;

    /**
     * Decimal precision and scale.
     *
     * Precision is the total count of significant digits in the whole number,
     * on both sides of the decimal point. Scale is the count of decimal digits
     * in the fractional part, to the right of the decimal point. This is
     * defined in the database as `decimal(32,16))` and enforced in code via
     * `self::NUMBER_PATTERN`.
     */
    const DECIMAL_PRECISION = 32;
    const DECIMAL_SCALE = 16;

    /**
     * The regex pattern for a valid number
     *
     * A valid number is an integer or decimal. A decimal must be within the
     * limits of precision and scale.
     */
    const NUMBER_PATTERN = '^-?(\d{0,16})((\.)(\d{1,16}))?$';

    public function getName()
    {
        return 'numeric:integer';
    }

    public function getLabel()
    {
        return 'Number'; // @translate
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return ['@value' => $value->value()];
        }
        if (strpos($value->value(), '.')) {
            $type = 'http://www.w3.org/2001/XMLSchema#decimal';
        } else {
            $type = 'http://www.w3.org/2001/XMLSchema#integer';
        }
        return [
            '@value' => $value->value(),
            '@type' => $type,
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
            && ((int) $valueObject['@value'] >= self::MIN_SAFE_INT)
            && preg_match(sprintf('/%s/', self::NUMBER_PATTERN), (string) $valueObject['@value']);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value, $options = [])
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return $value->value();
        }
        // Must use PREG_UNMATCHED_AS_NULL to ensure consistent matches array size.
        preg_match(sprintf('/%s/', self::NUMBER_PATTERN), $value->value(), $matches, PREG_UNMATCHED_AS_NULL);
        // Set a thin space as the thousands_separator, as per ISO standard.
        $wholePart = number_format($matches[1], 0, null, ' ');
        $decimalSeparator = $matches[3];
        $fractionalPart = $matches[4];
        return $wholePart . $decimalSeparator . $fractionalPart;
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesInteger';
    }

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        $entity->setValue($value->getValue());
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
        if (isset($query['numeric']['int']['lt']['val'])) {
            $value = $query['numeric']['int']['lt']['val'];
            $propertyId = $query['numeric']['int']['lt']['pid'] ?? null;
            if ($this->isValid(['@value' => $value])) {
                $this->addLessThanQuery($adapter, $qb, $propertyId, $value);
            }
        }
        if (isset($query['numeric']['int']['gt']['val'])) {
            $value = $query['numeric']['int']['gt']['val'];
            $propertyId = $query['numeric']['int']['gt']['pid'] ?? null;
            if ($this->isValid(['@value' => $value])) {
                $this->addGreaterThanQuery($adapter, $qb, $propertyId, $value);
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

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $this->form($view);
    }
}
