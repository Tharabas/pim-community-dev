<?php

namespace Pim\Component\Localization\Presenter;

use Pim\Component\Localization\Localizer\DateLocalizer;

/**
 * Date presenter, able to render date readable for a human
 *
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DatePresenter implements PresenterInterface
{
    /** @var DateLocalizer */
    protected $dateLocalizer;

    /** @var string[] */
    protected $attributeTypes;

    /**
     * @param DateLocalizer $dateLocalizer
     * @param string[]      $attributeTypes
     */
    public function __construct(DateLocalizer $dateLocalizer, array $attributeTypes)
    {
        $this->dateLocalizer  = $dateLocalizer;
        $this->attributeTypes = $attributeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function present($value, array $options = [])
    {
        // TODO Add specs
        // TODO Refactor after #4999 merge with DateFactory
        return $this->dateLocalizer->localize($value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($attributeType)
    {
        return in_array($attributeType, $this->attributeTypes);
    }
}
