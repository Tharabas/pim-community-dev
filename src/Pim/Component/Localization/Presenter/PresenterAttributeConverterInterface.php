<?php

namespace Pim\Component\Localization\Presenter;

/**
 * Interface PresenterAttributeConverterInterface
 *
 * Used to convert attribute values to be presented to user
 *
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface PresenterAttributeConverterInterface
{
    /**
     * Convert an attribute value to be presentable
     *
     * @param string $code
     * @param mixed  $data
     * @param array  $options
     *
     * @return mixed
     */
    public function convert($code, $data, array $options = []);
}
