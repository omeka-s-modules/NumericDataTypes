<?php
namespace NumericDataTypes\Service\FacetType;

use NumericDataTypes\FacetType\ValueLessThan;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ValueLessThanFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ValueLessThan($services->get('FormElementManager'));
    }
}
