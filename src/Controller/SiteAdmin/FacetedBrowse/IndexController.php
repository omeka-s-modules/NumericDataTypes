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
        $query = $this->params()->fromQuery('query');
        parse_str($query, $query);
        $query['site_id'] = $this->currentSite()->id();

        $api = $this->services->get('Omeka\ApiManager');
        $em = $this->services->get('Omeka\EntityManager');

        // Get the IDs of all items that satisfy the category query.
        $ids = $api->search('items', $query, ['returnScalar' => 'id'])->getContent();

        // Get all unique literal values of the specified property of the
        // specified items.
        $dql = '
        SELECT v.value value, COUNT(v.value) value_count
        FROM Omeka\Entity\Value v
        WHERE v.type = :type
        AND v.property = :propertyId
        AND v.resource IN (:ids)
        GROUP BY value
        ORDER BY value ASC';
        $query = $em->createQuery($dql)
            ->setParameter('type', 'numeric:timestamp')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('ids', $ids);
        $values = $query->getResult();

        $response = $this->getResponse();
        $responseHeaders = $response->getHeaders();
        $responseHeaders->addHeaderLine('Content-Type: application/json');
        $response->setContent(json_encode($values));
        return $response;
    }
}
