<?php
namespace NumericDataTypes\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class Interval extends AbstractHelper
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
<div class="numeric-interval">
    %s
    <div class="numeric-datetime-inputs numeric-interval-start">
        <span>%s</span>
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
    <div class="numeric-datetime-inputs numeric-interval-end">
        <span>%s</span>
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
            $view->translate('Start:'),
            $view->formNumber($element->getYearElement()),
            $view->formSelect($element->getMonthElement()),
            $view->formNumber($element->getDayElement()),
            $view->translate('time'),
            $view->formNumber($element->getHourElement()),
            $view->formNumber($element->getMinuteElement()),
            $view->formNumber($element->getSecondElement()),
            $view->translate('End:'),
            $view->formNumber($element->getYearElement()),
            $view->formSelect($element->getMonthElement()),
            $view->formNumber($element->getDayElement()),
            $view->translate('time'),
            $view->formNumber($element->getHourElement()),
            $view->formNumber($element->getMinuteElement()),
            $view->formNumber($element->getSecondElement())
        );
    }
}

