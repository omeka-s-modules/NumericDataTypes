<?php
namespace NumericDataTypes\DataType;

use DateInterval;
use Doctrine\ORM\QueryBuilder;
use Omeka\Entity\Value;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

class Duration extends AbstractDataType
{
    /**
     * Seconds in a timespan
     */
    const SECONDS_YEAR = 31536000; // 365 day year
    const SECONDS_MONTH = 2592000; // 30 day month
    const SECONDS_DAY = 86400;
    const SECONDS_HOUR = 3600;
    const SECONDS_MINUTE = 60;

    public function getName()
    {
        return 'numeric:duration';
    }

    public function getLabel()
    {
        return 'Duration'; // @translate
    }

    public function form(PhpRenderer $view)
    {
        $valueInput = new Element\Hidden('numeric-duration-value');
        $valueInput->setAttributes([
            'data-value-key' => '@value',
        ]);

        $yearsInput = new Element\Number('numeric-duration-years');
        $yearsInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Years (365 days each)', // @translate
        ]);

        $monthsInput = new Element\Number('numeric-duration-months');
        $monthsInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Months (30 days each)', // @translate
        ]);

        $daysInput = new Element\Number('numeric-duration-days');
        $daysInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Days', // @translate
        ]);

        $hoursInput = new Element\Number('numeric-duration-hours');
        $hoursInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Hours', // @translate
        ]);

        $minutesInput = new Element\Number('numeric-duration-minutes');
        $minutesInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Minutes', // @translate
        ]);

        $secondsInput = new Element\Number('numeric-duration-seconds');
        $secondsInput->setAttributes([
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Seconds', // @translate
        ]);

        $html = <<<HTML
<div class="duration-datetime-inputs">
    %s
    <div class="timestamp-date-inputs">
        %s
        %s
        %s
    </div>
    <div class="timestamp-time-inputs">
        %s
        %s
        %s
    </div>
</div>
HTML;
        return sprintf(
            $html,
            $view->formHidden($valueInput),
            $view->formNumber($yearsInput),
            $view->formNumber($monthsInput),
            $view->formNumber($daysInput),
            $view->formNumber($hoursInput),
            $view->formNumber($minutesInput),
            $view->formNumber($secondsInput)
        );
    }

    public function isValid(array $valueObject)
    {
        try {
            $this->getDurationFromValue($valueObject['@value']);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        // Store the duration in ISO 8601, allowing for reduced precision.
        $duration = $this->getDurationFromValue($valueObject['@value']);
        $value->setValue($duration['duration_normalized']);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        $duration = $this->getDurationFromValue($value);
        $interval = $duration['interval'];
        $duration = [];
        if ($interval->y) {
            $duration[] = (1 === $interval->y)
                ? sprintf($view->translate('%s year'), $interval->y)
                : sprintf($view->translate('%s years'), $interval->y);
        }
        if ($interval->m) {
            $duration[] = (1 === $interval->m)
                ? sprintf($view->translate('%s month'), $interval->m)
                : sprintf($view->translate('%s months'), $interval->m);
        }
        if ($interval->d) {
            $duration[] = (1 === $interval->d)
                ? sprintf($view->translate('%s day'), $interval->d)
                : sprintf($view->translate('%s days'), $interval->d);
        }
        if ($interval->h) {
            $duration[] = (1 === $interval->h)
                ? sprintf($view->translate('%s hour'), $interval->h)
                : sprintf($view->translate('%s hours'), $interval->h);
        }
        if ($interval->i) {
            $duration[] = (1 === $interval->i)
                ? sprintf($view->translate('%s minute'), $interval->i)
                : sprintf($view->translate('%s minutes'), $interval->i);
        }
        if ($interval->s) {
            $duration[] = (1 === $interval->s)
                ? sprintf($view->translate('%s second'), $interval->s)
                : sprintf($view->translate('%s seconds'), $interval->s);
        }
        return implode(', ', $duration);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return [
            '@value' => $value->value(),
            '@type' => 'http://www.w3.org/2001/XMLSchema#duration',
        ];
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesDuration';
    }

    /**
     * Get the total seconds from the duration string.
     *
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value)
    {
        $duration = $this->getDurationFromValue($value);
        return $duration['total_seconds'];
    }

    /**
     * Get the normalized duration, total seconds, and DateInterval object from
     * an ISO 8601 duration string.
     *
     * Note that DateInterval does not allow fractions or negatives for any
     * parts of a duration. Also, it ignores weeks if days are given, and
     * converts weeks into days if days are not given. We accept these
     * limitations for expediency.
     *
     * Also used to validate the duration string since validation is a side
     * effect of parsing the string.
     *
     * @param string $value
     * @return array
     */
    public static function getDurationFromValue($value)
    {
        try {
            // Use DateInterval to parse and validate ISO 8601 specs.
            $interval = new DateInterval($value);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid duration string, must use ISO 8601 without fractions or negatives');
        }

        // Calculate the total seconds of the duration.
        $totalSeconds =
              ($interval->y * self::SECONDS_YEAR)
            + ($interval->m * self::SECONDS_MONTH)
            + ($interval->d * self::SECONDS_DAY)
            + ($interval->h * self::SECONDS_HOUR)
            + ($interval->i * self::SECONDS_MINUTE)
            + $interval->s;
        if (Integer::MAX_SAFE_INT < $totalSeconds) {
            throw new \InvalidArgumentException('Invalid duration, exceeds maximum safe integer');
        }

        // Normalize the duration string by removing weeks.
        $date = '';
        if ($interval->y) $date .= sprintf('%sY', $interval->y);
        if ($interval->m) $date .= sprintf('%sM', $interval->m);
        if ($interval->d) $date .= sprintf('%sD', $interval->d);
        $time = '';
        if ($interval->h) $time .= sprintf('%sH', $interval->h);
        if ($interval->i) $time .= sprintf('%sM', $interval->i);
        if ($interval->s) $time .= sprintf('%sS', $interval->s);
        if ($time) $time = sprintf('T%s', $time);
        $durationNormalized = sprintf('P%s%s', $date, $time);

        return [
            'interval' => $interval,
            'total_seconds' => $totalSeconds,
            'duration_normalized' => $durationNormalized,
        ];
    }

    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
        if (isset($query['numeric']['dur']['lt']['val'])
            && isset($query['numeric']['dur']['lt']['pid'])
            && is_numeric($query['numeric']['dur']['lt']['pid'])
        ) {
            $value = $query['numeric']['dur']['lt']['val'];
            $propertyId = $query['numeric']['dur']['lt']['pid'];
            $this->addLessThanQuery($adapter, $qb, $propertyId, $value);
        }
        if (isset($query['numeric']['dur']['gt']['val'])
            && isset($query['numeric']['dur']['gt']['pid'])
            && is_numeric($query['numeric']['dur']['gt']['pid'])
        ) {
            $value = $query['numeric']['dur']['gt']['val'];
            $propertyId = $query['numeric']['dur']['gt']['pid'];
            $this->addGreaterThanQuery($adapter, $qb, $propertyId, $value);
        }
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
        if ('duration' === $type) {
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
