<?php

namespace Pim\Component\Localization\Presenter;

use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;

/**
 * The PresenterRegistry registers the presenters to display attribute values readable information. The matching
 * presenters are returned from an attributeType or an option name.
 *
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PresenterRegistry implements PresenterRegistryInterface
{
    const PRODUCT_VALUE_TYPE = 'product_value';

    const ATTRIBUTE_OPTION_TYPE = 'attribute_option';

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var PresenterInterface[] */
    protected $presenters = [];

    public function __construct(AttributeRepositoryInterface $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function register(PresenterInterface $presenter, $type)
    {
        if (!isset($this->presenters[$type])) {
            $this->presenters[$type] = [];
        }
        $this->presenters[$type][] = $presenter;

        return $this;
    }

    public function getPresenterByAttributeCode($code)
    {
        $attribute = $this->attributeRepository->findOneByIdentifier($code);
        if (null === $attribute) {
            return null;
        }

        $attributeType = $attribute->getAttributeType();
        if (null === $attributeType) {
            return null;
        }

        return $this->getPresenter($attributeType, self::PRODUCT_VALUE_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeOptionPresenter($optionName)
    {
        return $this->getPresenter($optionName, self::ATTRIBUTE_OPTION_TYPE);
    }

    /**
     * Get a presenter supporting value and type
     *
     * @param string $value
     * @param string $type
     *
     * @return PresenterInterface|null
     */
    protected function getPresenter($value, $type)
    {
        if (isset($this->presenters[$type])) {
            foreach ($this->presenters[$type] as $presenter) {
                if ($presenter->supports($value)) {
                    return $presenter;
                }
            }
        }

        return null;
    }
}
