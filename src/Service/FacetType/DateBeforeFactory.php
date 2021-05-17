<?php
namespace NumericDataTypes\Service\FacetType;

use NumericDataTypes\FacetType\DateBefore;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DateBeforeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new DateBefore($services->get('FormElementManager'));
    }
}
