<?php

namespace Oro\Bundle\GridBundle\Datagrid\ORM;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as BaseProxyQuery;

use Oro\Bundle\GridBundle\Datagrid\ProxyQueryInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * TODO: This class should be refactored  (BAP-969).
 */
class ProxyQuery extends BaseProxyQuery implements ProxyQueryInterface
{
    /**
     * @var string
     */
    protected $idFieldName;

    /**
     * @var string
     */
    protected $rootAlias;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var array
     */
    protected $sortOrderList = array();

    /**
     * @var array
     */
    protected $selectWhitelist = array();

    /**
     * @var array
     */
    protected $queryHints = array();

    /**
     * Get query builder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Get records total count
     *
     * @return int
     */
    public function getTotalCount()
    {
        $qb = clone $this->getResultIdsQueryBuilder();
        $qb->setFirstResult(null);
        $qb->setMaxResults(null);
        $qb->resetDQLPart('orderBy');

        $query = $qb->getQuery();

        // parse and prepare SQL parameters
        $parser = new Parser($query);
        $parserResult = $parser->parse();
        $parameterMappings = $parserResult->getParameterMappings();

        list($sqlParameters, $parameterTypes) = $this->processParameterMappings($query, $parameterMappings);

        // prepare and execute SQL query
        $statement = $qb->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM (' . $query->getSQL() .') AS e',
            $sqlParameters,
            $parameterTypes
        );

        $result = $statement->fetchColumn();

        return $result ? (int)$result : 0;
    }

    /**
     * @param Query $query
     * @param array $paramMappings
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    protected function processParameterMappings(Query $query, $paramMappings)
    {
        $sqlParams = array();
        $types     = array();

        /** @var Parameter $parameter */
        foreach ($query->getParameters() as $parameter) {
            $key = $parameter->getName();

            if (!isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            $value = $query->processParameterValue($parameter->getValue());
            $type  = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : Query\ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];

            $value = array($value);
            $countValue = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) != count($types)) {
            throw QueryException::parameterTypeMissmatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return array($sqlParams, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params = array(), $hydrationMode = null)
    {
        $query = $this->getResultQueryBuilder()->getQuery();
        $this->applyQueryHints($query);
        return $query->execute($params, $hydrationMode);
    }

    /**
     * Get query builder for result query
     *
     * @return QueryBuilder
     */
    protected function getResultQueryBuilder()
    {
        $qb = clone $this->getQueryBuilder();

        $this->applyWhere($qb);
        $this->applyOrderByParameters($qb);

        return $qb;
    }

    /**
     * Apply where part on query builder
     *
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function applyWhere(QueryBuilder $qb)
    {
        $idx = $this->getResultIds();
        if (count($idx) > 0) {
            $qb->where(sprintf('%s IN (%s)', $this->getIdFieldFQN(), implode(',', $idx)));
            $qb->resetDQLPart('having');
            $qb->setMaxResults(null);
            $qb->setFirstResult(null);
            // Since DQL has been changed, some parameters potentially are not used anymore.
            $this->fixUnusedParameters($qb);
        }
    }

    /**
     * Removes unused parameters from query builder
     *
     * @param QueryBuilder $qb
     */
    protected function fixUnusedParameters(QueryBuilder $qb)
    {
        $dql = $qb->getDQL();
        $usedParameters = array();
        /** @var $parameter \Doctrine\ORM\Query\Parameter */
        foreach ($qb->getParameters() as $parameter) {
            if ($this->dqlContainsParameter($dql, $parameter->getName())) {
                $usedParameters[$parameter->getName()] = $parameter->getValue();
            }
        }
        $qb->setParameters($usedParameters);
    }

    /**
     * Returns TRUE if $dql contains usage of parameter with $parameterName
     *
     * @param string $dql
     * @param string $parameterName
     * @return bool
     */
    protected function dqlContainsParameter($dql, $parameterName)
    {
        if (is_numeric($parameterName)) {
            $pattern = sprintf('/\?%s[^\w]/', preg_quote($parameterName));
        } else {
            $pattern = sprintf('/\:%s[^\w]/', preg_quote($parameterName));
        }
        return (bool)preg_match($pattern, $dql . ' ');
    }

    /**
     * Apply order by part
     *
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    protected function applyOrderByParameters(QueryBuilder $queryBuilder)
    {
        foreach ($this->sortOrderList as $sortOrder) {
            $this->applySortOrderParameters($queryBuilder, $sortOrder);
        }
    }

    /**
     * Apply sorting
     *
     * @param QueryBuilder $queryBuilder
     * @param array $sortOrder
     */
    protected function applySortOrderParameters(QueryBuilder $queryBuilder, array $sortOrder)
    {
        list($sortExpression, $extraSelect) = $sortOrder;
        if ($extraSelect && !$this->hasSelectItem($queryBuilder, $sortExpression)) {
            $queryBuilder->addSelect($extraSelect);
        }
    }

    /**
     * Checks if select DQL part already has select expression with name
     *
     * @param QueryBuilder $queryBuilder
     * @param string $name
     * @return bool
     */
    protected function hasSelectItem(QueryBuilder $queryBuilder, $name)
    {
        $name = strtolower(trim($name));
        /** @var $select \Doctrine\ORM\Query\Expr\Select */
        foreach ($queryBuilder->getDQLPart('select') as $select) {
            foreach ($select->getParts() as $part) {
                $part = strtolower(trim($part));
                if ($part === $name) {
                    return true;
                } elseif (' as ' . $name === substr($part, -strlen(' as ' . $name))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Fetches ids of objects that query builder targets
     *
     * @return array
     */
    protected function getResultIds()
    {
        $idx = array();

        $query = $this->getResultIdsQueryBuilder()->getQuery();
        $this->applyQueryHints($query);
        $results = $query->execute(array(), Query::HYDRATE_ARRAY);

        $connection = $this->getQueryBuilder()->getEntityManager()->getConnection();
        foreach ($results as $id) {
            $idx[] = $connection->quote($id[$this->getIdFieldName()]);
        }

        return $idx;
    }

    /**
     * Creates query builder that selects only id's of result objects
     *
     * @return QueryBuilder
     */
    protected function getResultIdsQueryBuilder()
    {
        $qb = clone $this->getQueryBuilder();

        // Apply orderBy before change select, because it can contain some expressions from select as aliases
        $this->applyOrderByParameters($qb);

        $selectExpressions = array('DISTINCT ' . $this->getIdFieldFQN());
        // We must leave expressions used in having
        $selectExpressions = array_merge($selectExpressions, $this->selectWhitelist);
        $qb->select($selectExpressions);

        // adding of sort by parameters to select
        // TODO move this logic to addOrderBy method after removing of flexible entity
        /** @var $orderExpression Query\Expr\OrderBy */
        foreach ($qb->getDQLPart('orderBy') as $orderExpression) {
            foreach ($orderExpression->getParts() as $orderString) {
                $orderField = trim(str_ireplace(array(' asc', ' desc'), '', $orderString));
                if (!$this->hasSelectItem($qb, $orderField)) {
                    $qb->addSelect($orderField);
                }
            }
        }

        // Since DQL has been changed, some parameters potentially are not used anymore.
        $this->fixUnusedParameters($qb);

        return $qb;
    }

    /**
     * Check whether provided expression already in select clause
     *
     * @param QueryBuilder $qb
     * @param string $selectString
     * @return bool
     */
    protected function isInSelectExpression(QueryBuilder $qb, $selectString)
    {
        /** @var $selectPart \Doctrine\ORM\Query\Expr\Select */
        foreach ($qb->getDQLPart('select') as $selectPart) {
            if (in_array($selectString, $selectPart->getParts())) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function addSortOrder(array $parentAssociationMappings, array $fieldMapping, $direction = null)
    {
        $alias = $this->entityJoin($parentAssociationMappings);
        if (!empty($fieldMapping['entityAlias'])) {
            $alias = $fieldMapping['entityAlias'];
        }

        $extraSelect = null;
        if (!empty($fieldMapping['fieldExpression']) && !empty($fieldMapping['fieldName'])) {
            $sortExpression = $fieldMapping['fieldName'];
            $extraSelect = sprintf('%s AS %s', $fieldMapping['fieldExpression'], $fieldMapping['fieldName']);
        } elseif (!empty($fieldMapping['fieldName'])) {
            $sortExpression = $this->getFieldFQN($fieldMapping['fieldName'], $alias);
        } else {
            throw new \LogicException('Cannot add sorting order, unknown field name in $fieldMapping.');
        }

        $this->getQueryBuilder()->addOrderBy($sortExpression, $direction);
        $this->sortOrderList[] = array($sortExpression, $extraSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function entityJoin(array $associationMappings)
    {
        $aliases = $this->getQueryBuilder()->getRootAliases();
        $alias = array_shift($aliases);

        $newAlias = 's';

        foreach ($associationMappings as $associationMapping) {
            $newAlias .= '_' . $associationMapping['fieldName'];
            if (!in_array($newAlias, $this->entityJoinAliases)) {
                $this->entityJoinAliases[] = $newAlias;
                $this->getQueryBuilder()
                    ->leftJoin($this->getFieldFQN($associationMapping['fieldName'], $alias), $newAlias);
            }

            $alias = $newAlias;
        }

        return $alias;
    }

    /**
     * Gets the root alias of the query
     *
     * @return string
     */
    public function getRootAlias()
    {
        if (!$this->rootAlias) {
            $this->rootAlias = current($this->getQueryBuilder()->getRootAliases());
        }
        return $this->rootAlias;
    }

    /**
     * Retrieve the column id of the targeted class
     *
     * @return string
     */
    protected function getIdFieldName()
    {
        if (!$this->idFieldName) {
            /** @var $from \Doctrine\ORM\Query\Expr\From */
            $from  = current($this->getQueryBuilder()->getDQLPart('from'));
            $class = $from->getFrom();

            $idNames = $this->getQueryBuilder()
                ->getEntityManager()
                ->getMetadataFactory()
                ->getMetadataFor($class)
                ->getIdentifierFieldNames();

            $this->idFieldName = current($idNames);
        }

        return $this->idFieldName;
    }

    /**
     * Get id field fully qualified name
     *
     * @return string
     */
    protected function getIdFieldFQN()
    {
        return $this->getFieldFQN($this->getIdFieldName());
    }

    /**
     * Get fields fully qualified name
     *
     * @param string $fieldName
     * @param string|null $parentAlias
     * @return string
     */
    protected function getFieldFQN($fieldName, $parentAlias = null)
    {
        if (strpos($fieldName, '.') === false) { // add the current alias
            $fieldName = ($parentAlias ? : $this->getRootAlias()) . '.' . $fieldName;
        }
        return $fieldName;
    }

    /**
     * Proxy of QueryBuilder::addSelect with flag that specified whether add select to internal whitelist
     *
     * @param string $select
     * @param bool $addToWhitelist
     * @return ProxyQuery
     */
    public function addSelect($select = null, $addToWhitelist = false)
    {
        if (empty($select)) {
            return $this;
        }

        if (is_array($select)) {
            $selects = $select;
        } else {
            $arguments = func_get_args();
            $lastElement = end($arguments);
            if (is_bool($lastElement)) {
                $selects = array_slice($arguments, 0, -1);
                $addToWhitelist = $lastElement;
            } else {
                $selects = $arguments;
            }
        }

        if ($addToWhitelist) {
            $this->selectWhitelist = array_merge($this->selectWhitelist, $selects);
        }

        $queryBuilder = $this->getQueryBuilder();
        foreach ($selects as $select) {
            if (!$addToWhitelist || $addToWhitelist && !$this->isInSelectExpression($queryBuilder, $select)) {
                $queryBuilder->addSelect($select);
            }
        }

        return $this;
    }

    /**
     * Set query parameter
     *
     * @param string $name
     * @param mixed $value
     * @return ProxyQuery
     */
    public function setParameter($name, $value)
    {
        $this->getQueryBuilder()->setParameter($name, $value);

        return $this;
    }

    /**
     * Sets a query hint
     *
     * @param string $name
     * @param mixed $value
     * @return ProxyQuery
     */
    public function setQueryHint($name, $value)
    {
        $this->queryHints[$name] = $value;

        return $this;
    }

    /**
     * @param AbstractQuery $query
     */
    protected function applyQueryHints(AbstractQuery $query)
    {
        foreach ($this->queryHints as $name => $value) {
            $query->setHint($name, $value);
        }
    }
}
