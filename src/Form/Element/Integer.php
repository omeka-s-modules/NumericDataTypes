<?php
namespace NumericDataTypes\Form\Element;

use NumericDataTypes\DataType\Integer as IntegerDataType;
use Laminas\Form\Element;

class Integer extends Element
{
    protected $valueElement;
    protected $integerElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->valueElement = (new Element\Hidden($name))
            ->setAttribute('class', 'numeric-integer-value to-require');
        $this->integerElement = (new Element\Text('integer'))
            ->setAttributes([
                'class' => 'numeric-integer-integer',
                'pattern' => IntegerDataType::NUMBER_PATTERN,
                'aria-label' => 'Number', // @translate
            ]);
    }

    public function getValueElement()
    {
        $this->valueElement->setValue($this->getValue());
        return $this->valueElement;
    }

    public function getIntegerElement()
    {
        return $this->integerElement;
    }
}
