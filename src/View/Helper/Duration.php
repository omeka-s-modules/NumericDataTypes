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
        $html = <<<HTML
<div class="numeric-duration">
    %s
    <div class="input">
        <label class="value">%s%s</label>
    </div>
    <div class="input">
        <label class="value">%s%s</label>
    </div>
    <div class="input">
        <label class="value">%s%s</label>
    </div>
    <div class="input">
        <label class="value">%s%s</label>
    </div>
    <div class="input">
        <label class="value">%s%s</label>
    </div>
    <div class="input">
        <label class="value">%s%s</label>
    </div>
</div>
HTML;
        return sprintf(
            $html,
            $view->formHidden($element->getValueElement()),
            $view->translate('Years'),
            $view->formNumber($element->getYearsElement()),
            $view->translate('Months'),
            $view->formNumber($element->getMonthsElement()),
            $view->translate('Days'),
            $view->formNumber($element->getDaysElement()),
            $view->translate('Hours'),
            $view->formNumber($element->getHoursElement()),
            $view->translate('Minutes'),
            $view->formNumber($element->getMinutesElement()),
            $view->translate('Seconds'),
            $view->formNumber($element->getSecondsElement())
        );
    }
}
