<?php

namespace Pim\Component\Localization\Presenter;

/**
 * PresenterRegistryInterface
 *
 * Used to implement registries to manage presenters
 *
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface PresenterRegistryInterface
{
    /**
     * Register a Presenter to the registry
     *
     * @param PresenterInterface $presenter
     */
    public function registerPresenter(PresenterInterface $presenter);

    /**
     * Get the first matching presenter supporting the attribute type.
     *
     * @param string $attributeType
     *
     * @return null|PresenterInterface
     */
    public function getPresenter($attributeType);

    /**
     * Register a Presenter to the registry to manage attribute options
     *
     * @param PresenterInterface $presenter
     */
    public function registerAttributeOptionPresenter(PresenterInterface $presenter);

    /**
     * Get the first matching presenter supporting the attribute option.
     *
     * @param string $attributeName
     *
     * @return null|PresenterInterface
     */
    public function getAttributeOptionPresenter($attributeName);
}
