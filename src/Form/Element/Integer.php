<?php
namespace NumericDataTypes\Form\Element;

use NumericDataTypes\DataType\Integer as IntegerDataType;
use Zend\Form\Element;

class Integer extends Element\Number
{
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->setAttributes([
            'step' => 1,
            'min' => IntegerDataType::MIN_SAFE_INT,
            'max' => IntegerDataType::MAX_SAFE_INT,
        ]);
    }
}
