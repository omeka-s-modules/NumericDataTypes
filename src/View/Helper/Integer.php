<?php
namespace NumericDataTypes\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class Integer extends AbstractHelper
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
<div class="numeric-integer">
    %s
    <div class="numeric-integer-inputs">
        %s
    </div>
</div>
HTML;
        return sprintf(
            $html,
            $view->formHidden($element->getValueElement()),
            $view->formNumber($element->getIntegerElement())
        );
    }
}
