<?php
namespace NumericDataTypes\Entity;

/**
 * @Entity
 */
class NumericDataTypesDuration extends NumericDataTypesNumber
{
    /**
     * @Column(type="bigint")
     */
    protected $value;
}
