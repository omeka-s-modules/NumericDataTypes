<?php
namespace NumericDataTypes\Service\FacetType;

use NumericDataTypes\FacetType\DurationLessThan;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DurationLessThanFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new DurationLessThan($services->get('FormElementManager'));
    }
}
