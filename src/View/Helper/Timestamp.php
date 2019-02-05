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
        $html = <<<HTML
<div class="numeric-timestamp">
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
