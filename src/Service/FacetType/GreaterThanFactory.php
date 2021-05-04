<?php
namespace NumericDataTypes\Service\FacetType;

use NumericDataTypes\FacetType\GreaterThan;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class GreaterThanFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new GreaterThan($services->get('FormElementManager'));
    }
}
