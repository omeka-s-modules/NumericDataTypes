<?php
namespace NumericDataTypes\Form\Element;

use Doctrine\ORM\EntityManager;
use Zend\Form\Element\Select;

class NumericPropertySelect extends Select
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return ApiManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Get value options for properties of numeric data types.
     *
     * @return array
     */
    public function getValueOptions()
    {
        $dataType = $this->getOption('numeric_data_type');
        if (!$dataType) {
            return [];
        }

        // Get only the properties of the numeric data types.
        $dql = 'SELECT p FROM Omeka\Entity\ResourceTemplateProperty p WHERE p.dataType = :dataType';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('dataType', sprintf('numeric:%s', $dataType));

        $valueOptions = [];
        foreach ($query->getResult() as $templateProperty) {
            $property = $templateProperty->getProperty();
            $template = $templateProperty->getResourceTemplate();
            if (!isset($valueOptions[$property->getId()])) {
                $valueOptions[$property->getId()] = [
                    'label' => sprintf('%s (%s)', $property->getLabel(), $template->getLabel()),
                    'value' => $property->getId(),
                    'alternate_labels' => [],
                ];
            }
            $valueOptions[$property->getId()]['alternate_labels'][] = $templateProperty->getAlternateLabel();
        }
        // Include alternate labels, if any.
        foreach ($valueOptions as $propertyId => $option) {
            $altLabels = array_unique(array_filter($valueOptions[$propertyId]['alternate_labels']));
            if ($altLabels) {
                $valueOptions[$propertyId]['label'] = sprintf(
                    '%s: %s',
                    $valueOptions[$propertyId]['label'],
                    implode('; ', $altLabels)
                );
            }
        }
        // Sort options alphabetically.
        usort($valueOptions, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        return $valueOptions;
    }
}
