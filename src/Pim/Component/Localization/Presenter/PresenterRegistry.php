<?php

namespace Pim\Component\Localization\Presenter;

/**
 * The PresenterRegistry registers the presenters to display attribute values readable information. The matching
 * presenters are returned from an attributeType
 *
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PresenterRegistry implements PresenterRegistryInterface
{
    /** @var PresenterInterface[] */
    protected $presenters = [];

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

    /**
     * {@inheritdoc}
     */
    public function getAttributePresenter($attributeType)
    {
        return $this->getPresenter($attributeType, 'attribute');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeOptionPresenter($optionName)
    {
        return $this->getPresenter($optionName, 'attribute_option');
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
