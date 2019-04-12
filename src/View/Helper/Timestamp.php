<?php
namespace NumericDataTypes\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class Timestamp extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $view->headLink()->appendStylesheet(
            $view->assetUrl('css/numeric-data-types.css', 'NumericDataTypes')
        );
        $view->headScript()->appendFile(
            $view->assetUrl('js/numeric-data-types.js', 'NumericDataTypes')
        );
        $html = <<<HTML
<div class="numeric-timestamp">
    <div class="error invalid-value" data-invalid-message="%s" data-custom-validity="%s"></div>
    %s
    <div class="numeric-datetime-inputs">
        <div class="numeric-date-inputs">
            %s
            %s
            %s
            <a href="#" class="numeric-toggle-time">%s</a>
        </div>
        <div class="numeric-time-inputs">
            %s
            %s
            %s
            %s
        </div>
    </div>
</div>
HTML;
        return sprintf(
            $html,
            $view->escapeHtml($view->translate('Invalid value: %s')),
            $view->escapeHtml($view->translate('Value must be a datetime')),
            $view->formHidden($element->getValueElement()),
            $view->formNumber($element->getYearElement()),
            $view->formSelect($element->getMonthElement()),
            $view->formSelect($element->getDayElement()),
            $view->translate('time'),
            $view->formSelect($element->getHourElement()),
            $view->formSelect($element->getMinuteElement()),
            $view->formSelect($element->getSecondElement()),
            $view->formSelect($element->getOffsetElement())
        );
    }
}
