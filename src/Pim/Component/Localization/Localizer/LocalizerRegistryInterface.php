<?php

namespace Pim\Component\Localization\Localizer;

use Pim\Component\Localization\Presenter\PresenterInterface;

/**
 * Register localizers interface. This interface manage two sets of localizers:
 * - the localizers for all the localizable attributes,
 * - the localizers for the ProductValue attributes.
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface LocalizerRegistryInterface
{
    /**
     * Register a localizer
     *
     * @param LocalizerInterface $localizer
     * @param string             $type
     */
    public function register(LocalizerInterface $localizer, $type);

    /**
     * Get the first presenter supporting an attribute option
     *
     * @param string $attributeType
     *
     * @return PresenterInterface|null
     */
    public function getAttributeOptionLocalizer($attributeType);

    /**
     * Get the first presenter supporting a product value
     *
     * @param string $attributeType
     *
     * @return PresenterInterface|null
     */
    public function getProductValueLocalizer($attributeType);
}
