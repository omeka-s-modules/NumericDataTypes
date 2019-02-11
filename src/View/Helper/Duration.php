<?php
namespace NumericDataTypes\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class Duration extends AbstractHelper
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
<div class="numeric-duration">
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
        </div>
    </div>
</div>
HTML;
        return sprintf(
            $html,
            $view->formHidden($element->getValueElement()),
            $view->formNumber($element->getYearsElement()),
            $view->formNumber($element->getMonthsElement()),
            $view->formNumber($element->getDaysElement()),
            $view->translate('time'),
            $view->formNumber($element->getHoursElement()),
            $view->formNumber($element->getMinutesElement()),
            $view->formNumber($element->getSecondsElement())
        );
    }
}
