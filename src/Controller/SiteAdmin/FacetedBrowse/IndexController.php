<?php
namespace NumericDataTypes\Controller\SiteAdmin\FacetedBrowse;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;

/**
 * Legacy endpoints for the "show all available values" table.
 *
 * Retained for installs whose FacetedBrowse predates getShowAllValues(): there the
 * data forms supply a "url" and the table is fetched from here. These actions are
 * shims over that same method, so there is one query implementation — but this
 * route carries no page ID, so it assumes items and gets item set and media pages
 * wrong. Upgrading FacetedBrowse is the fix.
 */
class IndexController extends AbstractActionController
{
    /**
     * Cap matching FacetedBrowse's own, so both paths behave alike.
     */
    const SHOW_ALL_LIMIT = 1000;

    protected $services;

    public function __construct(ServiceManager $services)
    {
        $this->services = $services;
    }

    public function timestampValuesAction()
    {
        return $this->getShowAllTable('date_after');
    }

    public function durationValuesAction()
    {
        return $this->getShowAllTable('duration_greater_than');
    }

    public function intervalValuesAction()
    {
        return $this->getShowAllTable('date_in_interval');
    }

    public function integerValuesAction()
    {
        return $this->getShowAllTable('value_greater_than');
    }

    /**
     * Delegate to a facet type's getShowAllValues().
     *
     * The facet type named here is whichever one shares this data type's query;
     * types that pair up, such as "date after" and "date before", return
     * identical rows, so either serves.
     */
    protected function getShowAllTable($facetTypeName)
    {
        $query = $this->params()->fromQuery('category_query');
        parse_str($query, $query);
        $query['site_id'] = $this->currentSite()->id();

        $api = $this->services->get('Omeka\ApiManager');

        // Assumes items; see the class docblock.
        $resourceIds = $api->search('items', $query, ['returnScalar' => 'id'])->getContent();

        $facetType = $this->services->get('FacetedBrowse\FacetTypeManager')->get($facetTypeName);
        $rows = $facetType->getShowAllValues([
            'resource_type' => 'items',
            'resource_entity_class' => 'Omeka\Entity\Item',
            // Doctrine cannot calculate IN() against an empty array.
            'resource_ids' => $resourceIds ?: [0],
            'data' => ['property_id' => $this->params()->fromQuery('property_id')],
            'sort_by' => 'label',
            'sort_order' => 'asc',
            'limit' => self::SHOW_ALL_LIMIT,
        ]);

        // No sortBy/sortOrder: this path offers no sort control, and the table
        // should not claim a direction FacetedBrowse did not choose.
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('faceted-browse/site-admin/category/show-all-table');
        $view->setVariable('rows', $rows);
        return $view;
    }
}
