<?php

namespace Oro\Bundle\FlexibleEntityBundle\Event;

use Oro\Bundle\FlexibleEntityBundle\Manager\FlexibleManager;
use Oro\Bundle\FlexibleEntityBundle\Model\AbstractAttribute;

/**
 * Filter event allows to know the create flexible attribute
 */
class FilterAttributeEvent extends AbstractFilterEvent
{
    /**
     * Flexible attribute
     * @var AbstractAttribute
     */
    protected $attribute;

    /**
     * Constructor
     *
     * @param FlexibleManager   $manager   the manager
     * @param AbstractAttribute $attribute the attribute
     */
    public function __construct(FlexibleManager $manager, AbstractAttribute $attribute)
    {
        parent::__construct($manager);
        $this->attribute = $attribute;
    }

    /**
     * @return AbstractAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }
}
