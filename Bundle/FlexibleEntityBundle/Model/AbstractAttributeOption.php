<?php

namespace Oro\Bundle\FlexibleEntityBundle\Model;

use Oro\Bundle\FlexibleEntityBundle\Model\Behavior\TranslatableInterface;

/**
 * Abstract entity attribute option, independent of storage
 */
abstract class AbstractAttributeOption implements TranslatableInterface
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var AbstractAttribute $attribute
     *
     */
    protected $attribute;

    /**
     * @var \ArrayAccess $optionValues
     */
    protected $optionValues;

    /**
     * @var boolean $translatable
     */
    protected $translatable;

    /**
     * Not persisted, allowe to define the value locale
     * @var string $locale
     */
    protected $locale;

    /**
     * @var integer $sortOrder
     */
    protected $sortOrder;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return AbstractAttributeOption
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get attribute
     *
     * @return AbstractAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set attribute
     *
     * @param AbstractAttribute $attribute
     *
     * @return AbstractAttributeOption
     */
    public function setAttribute(AbstractAttribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get values
     *
     * @return \ArrayAccess
     */
    public function getOptionValues()
    {
        return $this->optionValues;
    }

    /**
     * Get used locale
     *
     * @return string $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set used locale
     *
     * @param string $locale
     *
     * @return TranslatableInterface
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set translatable
     *
     * @param boolean $translatable
     *
     * @return AbstractAttributeOption
     */
    public function setTranslatable($translatable)
    {
        $this->translatable = $translatable;

        return $this;
    }

    /**
     * Get translatable
     *
     * @return boolean $translatable
     */
    public function getTranslatable()
    {
        return $this->translatable;
    }

    /**
     * Set sort order
     *
     * @param string $sortOrder
     *
     * @return AbstractAttributeOption
     */
    public function setSortOrder($sortOrder)
    {
        if ($sortOrder !== null) {
            $this->sortOrder = $sortOrder;
        }

        return $this;
    }

    /**
     * Get sort order
     *
     * @return integer
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}
