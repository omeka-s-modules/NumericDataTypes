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
            ->setAttribute('class', 'numeric-integer-value');
        $this->integerElement = (new Element\Number('integer'))
            ->setAttributes([
                'class' => 'numeric-integer-integer',
                'step' => 1,
                'min' => IntegerDataType::MIN_SAFE_INT,
                'max' => IntegerDataType::MAX_SAFE_INT,
            ]);
    }

    public function getValueElement()
    {
        return $this->valueElement;
    }

    public function getIntegerElement()
    {
        return $this->integerElement;
    }
}
