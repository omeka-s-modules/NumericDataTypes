<?php
namespace NumericDataTypes\Entity;

/**
 * @Entity
 */
class NumericDataTypesInteger extends NumericDataTypesNumber
{
    /**
     * @Column(type="bigint")
     */
    protected $value;
}
