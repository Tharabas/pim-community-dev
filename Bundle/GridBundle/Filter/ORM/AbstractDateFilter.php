<?php

namespace Oro\Bundle\GridBundle\Filter\ORM;

use Doctrine\DBAL\Query\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;

abstract class AbstractDateFilter extends AbstractFilter
{
    /**
     * DateTime object as string format
     */
    const DATETIME_FORMAT = 'Y-m-d';


    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return;
        }

        /** @var $dateStartValue \DateTime */
        $dateStartValue = $data['date_start'];
        /** @var $dateEndValue \DateTime */
        $dateEndValue = $data['date_end'];
        $type = $data['type'];

        $startDateParameterName = $this->getNewParameterName($queryBuilder);
        $endDateParameterName = $this->getNewParameterName($queryBuilder);

        if ($type == DateRangeFilterType::TYPE_NOT_BETWEEN) {
            $this->applyFilterNotBetween(
                $queryBuilder,
                $dateStartValue,
                $dateEndValue,
                $startDateParameterName,
                $endDateParameterName,
                $alias,
                $field
            );
        } else {
            $this->applyFilterBetween(
                $queryBuilder,
                $dateStartValue,
                $dateEndValue,
                $startDateParameterName,
                $endDateParameterName,
                $alias,
                $field
            );
        }

        /** @var $queryBuilder QueryBuilder */
        if ($dateStartValue) {
            $queryBuilder->setParameter($startDateParameterName, $dateStartValue);
        }
        if ($dateEndValue) {
            $queryBuilder->setParameter($endDateParameterName, $dateEndValue);
        }
    }

    /**
     * @param mixed $data
     * @return array|bool
     */
    public function parseData($data)
    {
        if (!$this->isValidData($data)) {
            return false;
        }

        if (isset($data['value']['start'])) {
            $data['value']['start'] = $data['value']['start']->format(static::DATETIME_FORMAT);
        } else {
            $data['value']['start'] = null;
        }

        if (isset($data['value']['end'])) {
            $data['value']['end'] = $data['value']['end']->format(static::DATETIME_FORMAT);
        } else {
            $data['value']['end'] = null;
        }

        $data['type'] = isset($data['type']) ? $data['type'] : null;

        if ($data['type'] != DateRangeFilterType::TYPE_NOT_BETWEEN) {
            $data['type'] = DateRangeFilterType::TYPE_BETWEEN;
        }

        return array(
            'date_start' => $data['value']['start'],
            'date_end' => $data['value']['end'],
            'type' => $data['type']
        );
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidData($data)
    {
        if (!is_array($data) || !array_key_exists('value', $data) || !is_array($data['value'])) {
            return false;
        }

        if (!isset($data['value']['start']) && !isset($data['value']['end'])) {
            return false;
        }

        // check start date
        if (isset($data['value']['start']) && !$data['value']['start'] instanceof \DateTime) {
            return false;
        }

        // check end date
        if (isset($data['value']['end']) && !$data['value']['end'] instanceof \DateTime) {
            return false;
        }

        return true;
    }

    /**
     * Apply expression using "between" filtering
     *
     * @param ProxyQueryInterface $queryBuilder
     * @param string $dateStartValue
     * @param string $dateEndValue
     * @param string $startDateParameterName
     * @param string $endDateParameterName
     * @param string $alias
     * @param string $field
     */
    protected function applyFilterBetween(
        $queryBuilder,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $alias,
        $field
    ) {
        if ($dateStartValue) {
            $this->applyFilterToClause(
                $queryBuilder,
                $this->createCompareFieldExpression($field, $alias, '>=', $startDateParameterName)
            );
        }

        if ($dateEndValue) {
            $this->applyFilterToClause(
                $queryBuilder,
                $this->createCompareFieldExpression($field, $alias, '<=', $endDateParameterName)
            );
        }
    }

    /**
     * Apply expression using "not between" filtering
     *
     * @param ProxyQueryInterface $queryBuilder
     * @param string $dateStartValue
     * @param string $dateEndValue
     * @param string $startDateParameterName
     * @param string $endDateParameterName
     * @param string $alias
     * @param string $field
     */
    protected function applyFilterNotBetween(
        $queryBuilder,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $alias,
        $field
    ) {
        $orExpression = $this->getExpressionFactory()->orX();

        if ($dateStartValue) {
            $orExpression->add($this->createCompareFieldExpression($field, $alias, '<', $startDateParameterName));
        }

        if ($dateEndValue) {
            $orExpression->add($this->createCompareFieldExpression($field, $alias, '>', $endDateParameterName));
        }

        $this->applyFilterToClause($queryBuilder, $orExpression);
    }
}
