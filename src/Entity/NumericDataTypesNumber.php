<?php
namespace NumericDataTypes\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;

/**
 * A numeric index over resource values, used for sorting and filtering.
 *
 * These tables are a derived cache, not a supported interface. They are rebuilt
 * from the values on api.hydrate.post, and the value string remains the record
 * of what was entered. Nothing outside this module should read them directly;
 * anything that does is undocumented and unsupported, and their contents may
 * change without notice.
 *
 * @MappedSuperclass
 * @Table(
 *     indexes={
 *         @Index(name="property_value", columns={"property_id", "value"}),
 *         @Index(name="value", columns={"value"}),
 *     }
 * )
 */
class NumericDataTypesNumber extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $resource;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Property"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $property;

    public function getId()
    {
        return $this->id;
    }

    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setProperty(Property $property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }
}
