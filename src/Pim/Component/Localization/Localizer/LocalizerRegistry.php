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
    const ATTRIBUTE_OPTION_TYPE = 'attribute_option';

    const PRODUCT_VALUE_TYPE = 'product_value';

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
        return $this->getLocalizer($attributeType, self::ATTRIBUTE_OPTION_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductValueLocalizer($attributeType)
    {
        return $this->getLocalizer($attributeType, self::PRODUCT_VALUE_TYPE);
    }

    /**
     * Get a localizer supported by value and type
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
