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
        return $this->getView()->partial('common/numeric-integer', ['element' => $element]);
    }
}
