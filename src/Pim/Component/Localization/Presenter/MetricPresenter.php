<?php

namespace Pim\Component\Localization\Presenter;

/**
 * Metric presenter, able to render metric data readable for a human
 *
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MetricPresenter extends AbstractNumberPresenter
{
    /**
     * {@inheritdoc}
     */
    public function present($value, array $options = [])
    {
        if (is_array($value) && isset($value['data'])) {
            $numberFormatter = $this->numberFactory->create($options);
            $amount = $numberFormatter->format($value['data']);

            return sprintf('%s %s', $amount, $value['unit']);
        }

        return parent::present($value, $options);
    }
}
