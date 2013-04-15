<?php
namespace Pim\Bundle\ProductBundle\Entity;

use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

use Doctrine\ORM\Mapping as ORM;

/**
 * Attribute group translation entity
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ORM\Entity()
 * @ORM\Table(
 *     name="pim_attribute_group_translations",
 *     indexes={
 *         @ORM\Index(
 *             name="pim_attribute_group_translations_idx",
 *             columns={"locale", "object_class", "field", "foreign_key"}
 *         )
 *     }
 * )
 *
 */
class AttributeGroupTranslation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */

    /**
     *
     * @ORM\ManyToOne(targetEntity="AttributeGroup", inversedBy="translations")
     * @ORM\JoinColumn(name="foreign_key", referencedColumnName="id")
     */
    protected $foreignKey;

    /**
     * *
     * @param unknown_type $locale
     * @param unknown_type $field
     * @param unknown_type $value
     */
    public function __construct($locale, $field, $value)
    {
        $this->setLocale($locale);
        $this->setField($field);
        $this->setContent($value);
    }
}
