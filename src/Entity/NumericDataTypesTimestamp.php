<?php
namespace NumericDataTypes\Entity;

/**
 * @Entity
 */
class NumericDataTypesTimestamp extends NumericDataTypesNumber
{
    /**
     * @Column(type="bigint")
     */
    protected $value;
}
