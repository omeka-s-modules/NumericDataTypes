<?php
namespace NumericDataTypes\FacetType;

use FacetedBrowse\Api\Representation\FacetedBrowseFacetRepresentation;
use FacetedBrowse\FacetType\FacetTypeInterface;
use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use NumericDataTypes\DataType\Timestamp;
use NumericDataTypes\Form\Element\NumericPropertySelect;

class GreaterThan implements FacetTypeInterface
{
    protected $formElements;

    public function __construct(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Greater than'; // @translate
    }

    public function getMaxFacets() : ?int
    {
        return 1;
    }

    public function prepareDataForm(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-data-form/greater-than.js', 'NumericDataTypes'));
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        // Property ID
        $propertyId = $this->formElements->get(NumericPropertySelect::class);
        $propertyId->setName('property_id');
        $propertyId->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => '',
            'numeric_data_type' => 'integer',
        ]);
        $propertyId->setAttributes([
            'id' => 'greater-than-property-id',
            'value' => $data['property_id'] ?? null,
            'data-placeholder' => 'Select one…', // @translate
        ]);
        // Minimum
        $min = $this->formElements->get(LaminasElement\Number::class);
        $min->setName('min');
        $min->setValue($data['min'] ?? null);
        $min->setOptions([
            'label' => 'Minimum value',
        ]);
        $min->setAttributes([
            'id' => 'greater-than-min',
        ]);
        // Maximum
        $max = $this->formElements->get(LaminasElement\Number::class);
        $max->setName('max');
        $max->setValue($data['max'] ?? null);
        $max->setOptions([
            'label' => 'Maximum value',
        ]);
        $max->setAttributes([
            'id' => 'greater-than-max',
        ]);
        // Step
        $step = $this->formElements->get(LaminasElement\Number::class);
        $step->setName('step');
        $step->setValue($data['step'] ?? null);
        $step->setOptions([
            'label' => 'Step',
        ]);
        $step->setAttributes([
            'id' => 'greater-than-step',
        ]);

        return $view->partial('common/faceted-browse/facet-data-form/greater-than', [
            'propertyId' => $propertyId,
            'min' => $min,
            'max' => $max,
            'step' => $step,
        ]);
    }

    public function prepareFacet(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-render/greater-than.js', 'NumericDataTypes'));
    }

    public function renderFacet(PhpRenderer $view, FacetedBrowseFacetRepresentation $facet) : string
    {
        $greaterThan = $this->formElements->get(LaminasElement\Range::class);
        $greaterThan->setName('greater_than');
        $greaterThan->setAttributes([
            'class' => 'greater-than',
            'min' => $facet->data('min'),
            'max' => $facet->data('max'),
            'step' => $facet->data('step'),
            'style' => 'width: 90%;',
        ]);

        return $view->partial('common/faceted-browse/facet-render/greater-than', [
            'facet' => $facet,
            'greaterThan' => $greaterThan,
        ]);
    }
}
