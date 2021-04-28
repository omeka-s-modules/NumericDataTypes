<?php
namespace NumericDataTypes\FacetType;

use FacetedBrowse\Api\Representation\FacetedBrowseFacetRepresentation;
use FacetedBrowse\FacetType\FacetTypeInterface;
use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use NumericDataTypes\DataType\Timestamp;
use NumericDataTypes\Form\Element\NumericPropertySelect;

class DateAfter implements FacetTypeInterface
{
    protected $formElements;

    public function __construct(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Date after'; // @translate
    }

    public function prepareDataForm(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-data-form/date-after.js', 'NumericDataTypes'));
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        // Property ID
        $propertyId = $this->formElements->get(NumericPropertySelect::class);
        $propertyId->setName('property_id');
        $propertyId->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => '',
            'numeric_data_type' => 'timestamp',
        ]);
        $propertyId->setAttributes([
            'id' => 'date-after-property-id',
            'value' => $data['property_id'] ?? null,
            'data-placeholder' => 'Select one…', // @translate
        ]);
        // Values
        $values = $this->formElements->get(LaminasElement\Textarea::class);
        $values->setName('values');
        $values->setOptions([
            'label' => 'Values', // @translate
            'info' => 'Enter the date/time values in ISO 8601 format, separated by new lines.', // @translate
        ]);
        $values->setAttributes([
            'id' => 'date-after-values',
            'style' => 'height: 300px;',
            'value' => $data['values'] ?? null,
        ]);
        return $view->partial('common/faceted-browse/facet-data-form/date-after', [
            'elementPropertyId' => $propertyId,
            'elementValues' => $values,
        ]);
    }

    public function prepareFacet(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-render/date_after.js', 'NumericDataTypes'));
    }

    public function renderFacet(PhpRenderer $view, FacetedBrowseFacetRepresentation $facet) : string
    {
        $values = $facet->data('values');
        $values = explode("\n", $values);
        $values = array_map('trim', $values);
        $values = array_unique($values);
        $values = array_combine($values, $values);
        $values = array_map(function ($value) {
            $date = Timestamp::getDateTimeFromValue($value);
            return $date['date']->format($date['format_render']);
        }, $values);

        $elementValues = $this->formElements->get(LaminasElement\Select::class);
        $elementValues->setName('date_after');
        $elementValues->setAttribute('class', 'date-after-value');
        $elementValues->setEmptyOption('Select a date…'); // @translate
        $elementValues->setValueOptions($values);

        return $view->partial('common/faceted-browse/facet-render/date-after', [
            'facet' => $facet,
            'elementValues' => $elementValues,
        ]);
    }
}
