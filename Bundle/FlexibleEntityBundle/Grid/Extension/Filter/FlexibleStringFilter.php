<?php

namespace Oro\Bundle\FlexibleEntityBundle\Grid\Extension\Filter;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Extension\Orm\StringFilter;

class FlexibleStringFilter extends StringFilter
{
    /** @var FlexibleFilterUtility */
    protected $util;

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
}
