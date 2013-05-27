<?php

namespace Oro\Bundle\GridBundle\Filter\ORM\Flexible;

use Doctrine\Common\Persistence\ObjectRepository;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FlexibleEntityBundle\Entity\Attribute;
use Oro\Bundle\FlexibleEntityBundle\Entity\AttributeOption;

use Oro\Bundle\GridBundle\Filter\ORM\ChoiceFilter;

class FlexibleOptionsFilter extends AbstractFlexibleFilter
{
    /**
     * @var array
     */
    protected $valueOptions;

    /**
     * @var string
     */
    protected $parentFilterClass = 'Oro\\Bundle\\GridBundle\\Filter\\ORM\\ChoiceFilter';

    /**
     * @var ChoiceFilter
     */
    protected $parentFilter;

    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $proxyQuery, $alias, $field, $data)
    {
        $data = $this->parentFilter->parseData($data);
        if (!$data) {
            return;
        }

        $operator = $this->parentFilter->getOperator($data['type']);

        // apply filter
        $this->applyFlexibleFilter($proxyQuery, $field, $data['value'], $operator);
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        list($formType, $formOptions) = parent::getRenderSettings();
        $formOptions['field_options']['choices'] = $this->getValueOptions();
        $formOptions['field_options']['multiple'] = $this->getOption('multiple') ? true : false;
        return array($formType, $formOptions);
    }

    /**
     * @return array
     * @throws \LogicException
     * @todo Make this method protected or private
     */
    public function getValueOptions()
    {
        if (null === $this->valueOptions) {
            $filedName = $this->getOption('field_name');
            $flexibleManager = $this->getFlexibleManager();

            /** @var $attributeRepository ObjectRepository */
            $attributeRepository = $flexibleManager->getAttributeRepository();
            /** @var $attribute Attribute */
            $attribute = $attributeRepository->findOneBy(
                array('entityType' => $flexibleManager->getFlexibleName(), 'code' => $filedName)
            );
            if (!$attribute) {
                throw new \LogicException('There is no flexible attribute with name ' . $filedName . '.');
            }

            /** @var $optionsRepository ObjectRepository */
            $optionsRepository = $flexibleManager->getAttributeOptionRepository();
            $options = $optionsRepository->findBy(
                array('attribute' => $attribute)
            );

            $this->valueOptions = array();
            /** @var $option AttributeOption */
            foreach ($options as $option) {
                $optionValue = $option->getOptionValue();
                if ($optionValue) {
                    $this->valueOptions[$option->getId()] = $optionValue->getValue();
                }
            }
        }

        return $this->valueOptions;
    }
}
