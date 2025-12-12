<?php
namespace NumericDataTypes\Entity;

/**
 * @Entity
 */
class NumericDataTypesInteger extends NumericDataTypesNumber
{
    /**
     * @Column(type="decimal", precision=32, scale=16)
     */
    protected $value;

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
