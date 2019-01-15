<?php
namespace NumericDataTypes;

use Doctrine\Common\Collections\Criteria;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $services)
    {
        $services->get('Omeka\Connection')->exec('
CREATE TABLE numeric_data_types_duration (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_E1B5FC6089329D25 (resource_id), INDEX IDX_E1B5FC60549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE numeric_data_types_integer (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_6D39C79089329D25 (resource_id), INDEX IDX_6D39C790549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE numeric_data_types_timestamp (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_7367AFAA89329D25 (resource_id), INDEX IDX_7367AFAA549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE numeric_data_types_interval (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, value2 BIGINT NOT NULL, INDEX IDX_7E2C936B89329D25 (resource_id), INDEX IDX_7E2C936B549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE numeric_data_types_duration ADD CONSTRAINT FK_E1B5FC6089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_duration ADD CONSTRAINT FK_E1B5FC60549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_integer ADD CONSTRAINT FK_6D39C79089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_integer ADD CONSTRAINT FK_6D39C790549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_timestamp ADD CONSTRAINT FK_7367AFAA89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_timestamp ADD CONSTRAINT FK_7367AFAA549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_interval ADD CONSTRAINT FK_7E2C936B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_interval ADD CONSTRAINT FK_7E2C936B549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $services->get('Omeka\Connection')->exec('
DROP TABLE IF EXISTS numeric_data_types_duration;
DROP TABLE IF EXISTS numeric_data_types_integer;
DROP TABLE IF EXISTS numeric_data_types_timestamp;
DROP TABLE IF EXISTS numeric_data_types_interval;
');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.add.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.edit.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.add.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'buildQueries']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'sortQueries']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'saveNumericData']
        );
        $sharedEventManager->attach(
            '*',
            'view.sort-selector',
            [$this, 'addSortings']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'common/numeric-data-types-advanced-search';
                $event->setParam('partials', $partials);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'common/numeric-data-types-advanced-search';
                $event->setParam('partials', $partials);
            }
        );
    }

    public function prepareResourceForm(Event $event)
    {
        $view = $event->getTarget();
        $view->headLink()->appendStylesheet($view->assetUrl('css/numeric-data-types.css', 'NumericDataTypes'));
        $view->headScript()->appendFile($view->assetUrl('js/numeric-data-types.js', 'NumericDataTypes'));
    }

    /**
     * Save numeric data to the corresponding number tables.
     *
     * This clears all existing numbers and (re)saves them during create and
     * update operations for a resource (item, item set, media). We do this as
     * an easy way to ensure that the numbers in the number tables are in sync
     * with the numbers in the value table.
     *
     * @param Event $event
     */
    public function saveNumericData(Event $event)
    {
        $entity = $event->getParam('entity');
        if (!$entity instanceof \Omeka\Entity\Resource) {
            // This is not a resource.
            return;
        }

        $allValues = $entity->getValues();
        foreach ($this->getNumericDataTypes() as $dataTypeName => $dataType) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('type', $dataTypeName));
            $matchingValues = $allValues->matching($criteria);
            if (!$matchingValues) {
                // This resource has no number values of this type.
                continue;
            }

            $em = $this->getServiceLocator()->get('Omeka\EntityManager');
            $existingNumbers = [];

            if ($entity->getId()) {
                $dql = sprintf('SELECT n FROM %s n WHERE n.resource = :resource', $dataType->getEntityClass());
                $query = $em->createQuery($dql);
                $query->setParameter('resource', $entity);
                $existingNumbers = $query->getResult();
            }
            foreach ($matchingValues as $value) {
                // Avoid ID churn by reusing number rows.
                $number = current($existingNumbers);
                if ($number === false) {
                    // No more number rows to reuse. Create a new one.
                    $entityClass = $dataType->getEntityClass();
                    $number = new $entityClass;
                    $em->persist($number);
                } else {
                    // Null out numbers as we reuse them. Note that existing
                    // numbers are already managed and will update during flush.
                    $existingNumbers[key($existingNumbers)] = null;
                    next($existingNumbers);
                }
                $number->setResource($entity);
                $number->setProperty($value->getProperty());
                $dataType->setEntityValues($number, $value);
            }
            // Remove any numbers that weren't reused.
            foreach ($existingNumbers as $existingNumber) {
                if (null !== $existingNumber) {
                    $em->remove($existingNumber);
                }
            }
        }
    }

    /**
     * Build numerical queries.
     *
     * @param Event $event
     */
    public function buildQueries(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        if (!isset($query['numeric'])) {
            return;
        }
        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        foreach ($this->getNumericDataTypes() as $dataTypeName => $dataType) {
            $dataType->buildQuery($adapter, $qb, $query);
        }
    }

    /**
     * Sort numerical queries.
     *
     * sort_by=numeric:<type>:<propertyId>
     *
     * @param Event $event
     */
    public function sortQueries(Event $event)
    {
        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        $query = $event->getParam('request')->getContent();

        if (!isset($query['sort_by']) || !is_string($query['sort_by'])) {
            return;
        }
        $sortBy = explode(':', $query['sort_by']);
        if (3 !== count($sortBy)) {
            return;
        }
        list($namespace, $type, $propertyId) = $sortBy;
        if ('numeric' !== $namespace || !is_string($type) || !is_numeric($propertyId)) {
            return;
        }
        foreach ($this->getNumericDataTypes() as $dataTypeName => $dataType) {
            $dataType->sortQuery($adapter, $qb, $query, $type, $propertyId);
        }
    }

    /**
     * Add numeric sort options to sort by form.
     *
     * @param Event $event
     */
    public function addSortings(Event $event)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $numericSortBy = [];
        foreach ($this->getNumericDataTypes() as $dataTypeName => $dataType) {
            $dql = 'SELECT p FROM Omeka\Entity\ResourceTemplateProperty p WHERE p.dataType = :dataType';
            $query = $em->createQuery($dql);
            $query->setParameter('dataType', $dataType->getName());
            foreach ($query->getResult() as $templateProperty) {
                $property = $templateProperty->getProperty();
                $value = sprintf('%s:%s', $dataType->getName(), $property->getId());
                if (!isset($numericSortBy[$value])) {
                    $numericSortBy[$value] = [
                        'value' => $value,
                        'label' => $property->getLabel(),
                        'alternate_labels' => [],
                    ];
                }
                $numericSortBy[$value]['alternate_labels'][] = $templateProperty->getAlternateLabel();
            }
        }
        // Include alternate labels, if any.
        foreach ($numericSortBy as $key => $value) {
            $altLabels = array_unique(array_filter($numericSortBy[$key]['alternate_labels']));
            if ($altLabels) {
                $numericSortBy[$key]['label'] = sprintf(
                    '%s: %s',
                    $numericSortBy[$key]['label'],
                    implode('; ', $altLabels)
                );
            }
        }
        // Sort options alphabetically.
        usort($numericSortBy, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        $sortBy = $event->getParam('sortBy');
        $sortBy = array_merge($sortBy, $numericSortBy);
        $event->setParam('sortBy', $sortBy);
    }

    /**
     * Get all data types added by this module.
     *
     * @return array
     */
    public function getNumericDataTypes()
    {
        $dataTypes = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $numericDataTypes = [];
        foreach ($dataTypes->getRegisteredNames() as $dataType) {
            if (0 === strpos($dataType, 'numeric:')) {
                $numericDataTypes[$dataType] = $dataTypes->get($dataType);
            }
        }
        return $numericDataTypes;
    }
}
