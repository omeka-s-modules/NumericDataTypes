<?php
namespace NumericDataTypes\Service\FacetType;

use NumericDataTypes\FacetType\LessThan;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LessThanFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new LessThan($services->get('FormElementManager'));
    }
}
