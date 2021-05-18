<?php
namespace NumericDataTypes\Service\FacetType;

use NumericDataTypes\FacetType\DateInInterval;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DateInIntervalFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new DateInInterval($services->get('FormElementManager'));
    }
}
