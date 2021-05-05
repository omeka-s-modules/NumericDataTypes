<?php
namespace NumericDataTypes\Controller\SiteAdmin\FacetedBrowse;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $services;

    public function __construct(ServiceManager $services)
    {
        $this->services = $services;
    }
    public function timestampValuesAction()
    {
        $propertyId = $this->params()->fromQuery('property_id');
        $query = $this->params()->fromQuery('category_query');
        parse_str($query, $query);
        $query['site_id'] = $this->currentSite()->id();

        $api = $this->services->get('Omeka\ApiManager');
        $em = $this->services->get('Omeka\EntityManager');

        // Get the IDs of all items that satisfy the category query.
        $ids = $api->search('items', $query, ['returnScalar' => 'id'])->getContent();

        $dql = '
        SELECT v.value label, COUNT(v.value) has_count
        FROM Omeka\Entity\Value v
        WHERE v.type = :type
        AND v.property = :propertyId
        AND v.resource IN (:ids)
        GROUP BY label
        ORDER BY label ASC';
        $query = $em->createQuery($dql)
            ->setParameter('type', 'numeric:timestamp')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('ids', $ids);
        $values = $query->getResult();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('faceted-browse/site-admin/category/show-all-table');
        $view->setVariable('rows', $values);
        return $view;
    }

    public function integerValuesAction()
    {
        $propertyId = $this->params()->fromQuery('property_id');
        $query = $this->params()->fromQuery('category_query');
        parse_str($query, $query);
        $query['site_id'] = $this->currentSite()->id();

        $api = $this->services->get('Omeka\ApiManager');
        $em = $this->services->get('Omeka\EntityManager');

        // Get the IDs of all items that satisfy the category query.
        $ids = $api->search('items', $query, ['returnScalar' => 'id'])->getContent();

        // For ordering to work, we must cast string values to int by forcing a
        // cast using + 0.
        $dql = '
        SELECT v.value + 0 label, COUNT(v.value) has_count
        FROM Omeka\Entity\Value v
        WHERE v.type = :type
        AND v.property = :propertyId
        AND v.resource IN (:ids)
        GROUP BY label
        ORDER BY label ASC';
        $query = $em->createQuery($dql)
            ->setParameter('type', 'numeric:integer')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('ids', $ids);
        $values = $query->getResult();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('faceted-browse/site-admin/category/show-all-table');
        $view->setVariable('rows', $values);
        return $view;
    }
}
