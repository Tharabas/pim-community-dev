<?php

namespace Pim\Component\Localization\Localizer;

use Pim\Component\Localization\Presenter\PresenterInterface;

/**
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class LocalizerRegistry implements LocalizerRegistryInterface
{
    /** @var LocalizerInterface[] */
    protected $localizers = [];

    /**
     * {@inheritdoc}
     */
    public function register(LocalizerInterface $localizer, $type)
    {
        if (!isset($this->localizers[$type])) {
            $this->localizers[$type] = [];
        }
        $this->localizers[$type][] = $localizer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeOptionLocalizer($attributeType)
    {
        return $this->getLocalizer($attributeType, 'attribute_option');
    }

    /**
     * {@inheritdoc}
     */
    public function getProductValueLocalizer($attributeType)
    {
        return $this->getLocalizer($attributeType, 'product_value');
    }

    /**
     * Get a localizer supporting a value and a type
     *
     * @param string $value
     * @param string $type
     *
     * @return PresenterInterface|null
     */
    protected function getLocalizer($value, $type)
    {
        if (isset($this->localizers[$type])) {
            foreach ($this->localizers[$type] as $presenter) {
                if ($presenter->supports($value)) {
                    return $presenter;
                }
            }
        }

        return null;
    }
}
