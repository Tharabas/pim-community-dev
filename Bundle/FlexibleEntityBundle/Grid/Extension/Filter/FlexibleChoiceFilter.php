<?php

namespace Oro\Bundle\FlexibleEntityBundle\Grid\Extension\Filter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FlexibleEntityBundle\Entity\Attribute;
use Oro\Bundle\FilterBundle\Extension\Orm\ChoiceFilter;
use Oro\Bundle\FlexibleEntityBundle\Entity\AttributeOption;

class FlexibleChoiceFilter extends ChoiceFilter
{
    /** @var FlexibleFilterUtility */
    protected $util;

    /** @var array */
    protected $valueOptions;

    public function __construct(FormFactoryInterface $factory, FlexibleFilterUtility $util)
    {
        parent::__construct($factory);
        $this->util = $util;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $qb, $data)
    {
        $data = $this->parseData($data);
        if ($data) {
            $operator = $this->getOperator($data['type']);

            $fen = $this->get('flexible_entity_name');
            $this->util->applyFlexibleFilter($qb, $fen, $this->get('data_name'), $data['value'], $operator);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        $options = array_merge(
            $this->getOr('options', []),
            ['csrf_protection' => false]
        );

        $options['field_options']            = isset($options['field_options']) ? $options['field_options'] : [];
        $options['field_options']['choices'] = $this->getValueOptions();

        if (!$this->form) {
            $this->form = $this->formFactory->create($this->getFormType(), [], $options);
        }

        return $this->form;
    }

    /**
     * @return array
     * @throws \LogicException
     */
    protected function getValueOptions()
    {
        if (null === $this->valueOptions) {
            $filedName       = $this->get('data_name');
            $flexibleManager = $this->util->getFlexibleManager($this->get('flexible_entity_name'));

            /** @var $attributeRepository ObjectRepository */
            $attributeRepository = $flexibleManager->getAttributeRepository();
            /** @var $attribute Attribute */
            $attribute = $attributeRepository->findOneBy(
                ['entityType' => $flexibleManager->getFlexibleName(), 'code' => $filedName]
            );
            if (!$attribute) {
                throw new \LogicException('There is no flexible attribute with name ' . $filedName . '.');
            }

            /** @var $optionsRepository ObjectRepository */
            $optionsRepository  = $flexibleManager->getAttributeOptionRepository();
            $options            = $optionsRepository->findAllForAttributeWithValues($attribute);
            $this->valueOptions = [];
            /** @var $option AttributeOption */
            foreach ($options as $option) {
                $optionValue = $option->getOptionValue();
                if ($optionValue) {
                    $this->valueOptions[$option->getId()] = $optionValue->getValue();
                } else {
                    $this->valueOptions[$option->getId()] = '[' . $option->getCode() . ']';
                }
            }
        }

        return $this->valueOptions;
    }
}
