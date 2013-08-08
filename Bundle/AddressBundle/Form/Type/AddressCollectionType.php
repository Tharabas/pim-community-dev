<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class AddressCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_item_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_address_collection';
    }
}
